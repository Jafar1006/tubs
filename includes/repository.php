<?php
/**
 * Lapisan data: master data, pencarian gedung, dan CRUD.
 * Semua query memakai prepared statement (aman dari SQL injection).
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/* =====================================================================
   MASTER DATA
   ===================================================================== */

/** Peta tipe master -> nama tabel & label. */
function master_tables(): array
{
    return [
        'Kategori'   => ['table' => 'kategori',   'label' => 'Kategori'],
        'Fasilitas'  => ['table' => 'fasilitas',  'label' => 'Fasilitas'],
        'Jenis Acara' => ['table' => 'jenis_acara', 'label' => 'Jenis Acara'],
    ];
}

/**
 * @return array{Kategori: array<int,string>, Fasilitas: array<int,string>, 'Jenis Acara': array<int,string>}
 */
function masters(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    foreach (master_tables() as $key => $meta) {
        $rows = db()->query("SELECT id, nama FROM `{$meta['table']}` ORDER BY urutan, nama")->fetchAll();
        $cache[$key] = [];
        foreach ($rows as $row) {
            $cache[$key][(int) $row['id']] = $row['nama'];
        }
    }

    return $cache;
}

/** Cari id master berdasarkan nama (untuk seed / import CSV). */
function master_id_by_name(string $key, string $nama): ?int
{
    $id = array_search($nama, masters()[$key] ?? [], true);

    return $id === false ? null : (int) $id;
}

/** @return array{ok:bool, message:string} */
function master_add(string $key, string $nama): array
{
    $nama = trim($nama);
    if ($nama === '') {
        return ['ok' => false, 'message' => 'Nama tidak boleh kosong.'];
    }
    $table = master_tables()[$key]['table'] ?? null;
    if ($table === null) {
        return ['ok' => false, 'message' => 'Jenis master tidak dikenal.'];
    }
    if (master_id_by_name($key, $nama) !== null) {
        return ['ok' => false, 'message' => 'Data sudah ada.'];
    }
    $max = (int) db()->query("SELECT COALESCE(MAX(urutan), 0) FROM `$table`")->fetchColumn();
    $st  = db()->prepare("INSERT INTO `$table` (nama, urutan) VALUES (?, ?)");
    $st->execute([$nama, $max + 1]);

    return ['ok' => true, 'message' => 'Master data ditambahkan'];
}

/** @return array{ok:bool, message:string} */
function master_rename(string $key, int $id, string $nama): array
{
    $nama = trim($nama);
    if ($nama === '') {
        return ['ok' => false, 'message' => 'Nama harus diisi dan tidak boleh sama.'];
    }
    $table = master_tables()[$key]['table'] ?? null;
    if ($table === null) {
        return ['ok' => false, 'message' => 'Jenis master tidak dikenal.'];
    }
    $existing = master_id_by_name($key, $nama);
    if ($existing !== null && $existing !== $id) {
        return ['ok' => false, 'message' => 'Nama harus diisi dan tidak boleh sama.'];
    }
    $st = db()->prepare("UPDATE `$table` SET nama = ? WHERE id = ?");
    $st->execute([$nama, $id]);

    return ['ok' => true, 'message' => 'Master data diperbarui'];
}

/** @return array{ok:bool, message:string} */
function master_delete(string $key, int $id): array
{
    $table = master_tables()[$key]['table'] ?? null;
    if ($table === null) {
        return ['ok' => false, 'message' => 'Jenis master tidak dikenal.'];
    }

    if ($key === 'Kategori') {
        $st = db()->prepare('SELECT COUNT(*) FROM venues WHERE kategori_id = ?');
        $st->execute([$id]);
        $dipakai = (int) $st->fetchColumn();
        if ($dipakai > 0) {
            return ['ok' => false, 'message' => "Kategori masih dipakai oleh $dipakai gedung."];
        }
    }

    $st = db()->prepare("DELETE FROM `$table` WHERE id = ?");
    $st->execute([$id]);

    return ['ok' => true, 'message' => 'Master data dihapus'];
}

/* =====================================================================
   FILTER & PENCARIAN GEDUNG
   ===================================================================== */

/** Batas atas slider harga: minimal Rp 3.000.000, mengikuti harga termahal. */
function price_ceiling(): int
{
    static $ceiling = null;
    if ($ceiling === null) {
        $max = (int) db()->query('SELECT COALESCE(MAX(harga_per_jam), 0) FROM venues')->fetchColumn();
        $ceiling = max(3_000_000, $max);
    }

    return $ceiling;
}

/**
 * Bersihkan parameter filter dari query string.
 *
 * @return array{q:string,min:int,max:int,kapasitas:int,kecamatan:array<int,string>,kategori:array<int,string>,acara:array<int,string>,sort:string}
 */
function parse_filters(array $query): array
{
    $str = static fn (mixed $v): string => is_string($v) ? trim($v) : '';
    $list = static function (string $key) use ($query, $str): array {
        $values = $query[$key] ?? [];
        if (!is_array($values)) {
            $values = $values === '' ? [] : [$values];
        }
        $values = array_map($str, $values);

        return array_values(array_unique(array_filter($values, static fn ($v) => $v !== '')));
    };

    $ceiling = price_ceiling();
    $min     = max(0, (int) ($query['min'] ?? 0));
    $max     = isset($query['max']) && $query['max'] !== '' ? (int) $query['max'] : $ceiling;
    $max     = min($ceiling, max($min, $max));

    $sort = $str($query['sort'] ?? '');
    if (!in_array($sort, ['rendah', 'tinggi', 'kapasitas'], true)) {
        $sort = 'rekomendasi';
    }

    return [
        'q'         => $str($query['q'] ?? ''),
        'min'       => $min,
        'max'       => $max,
        'kapasitas' => max(0, (int) ($query['kapasitas'] ?? 0)),
        'kecamatan' => array_values(array_intersect(kecamatan_list(), $list('kecamatan'))),
        'kategori'  => $list('kategori'),
        'acara'     => $list('acara'),
        'sort'      => $sort,
    ];
}

/**
 * Jumlah gedung per kecamatan & per kategori (untuk angka di sidebar filter).
 *
 * @return array{kecamatan:array<string,int>, kategori:array<string,int>}
 */
function filter_counts(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = ['kecamatan' => [], 'kategori' => []];

    foreach (db()->query('SELECT kecamatan, COUNT(*) AS jumlah FROM venues GROUP BY kecamatan') as $row) {
        $cache['kecamatan'][$row['kecamatan']] = (int) $row['jumlah'];
    }
    $rows = db()->query('SELECT k.nama, COUNT(v.id) AS jumlah
                         FROM kategori k LEFT JOIN venues v ON v.kategori_id = k.id
                         GROUP BY k.id, k.nama');
    foreach ($rows as $row) {
        $cache['kategori'][$row['nama']] = (int) $row['jumlah'];
    }

    return $cache;
}

/** Jumlah filter aktif (untuk badge "N filter"). */
function active_filter_count(array $f): int
{
    return count($f['kecamatan']) + count($f['kategori']) + count($f['acara'])
        + ($f['min'] > 0 ? 1 : 0)
        + ($f['max'] < price_ceiling() ? 1 : 0)
        + ($f['kapasitas'] > 0 ? 1 : 0);
}

/** Bangun query string filter (untuk link reset / pertahankan filter). */
function filter_query(array $f, array $override = []): string
{
    $params = [];
    if ($f['q'] !== '') {
        $params['q'] = $f['q'];
    }
    if ($f['min'] > 0) {
        $params['min'] = $f['min'];
    }
    if ($f['max'] < price_ceiling()) {
        $params['max'] = $f['max'];
    }
    if ($f['kapasitas'] > 0) {
        $params['kapasitas'] = $f['kapasitas'];
    }
    foreach (['kecamatan', 'kategori', 'acara'] as $key) {
        if ($f[$key]) {
            $params[$key] = array_values($f[$key]);
        }
    }
    if ($f['sort'] !== 'rekomendasi') {
        $params['sort'] = $f['sort'];
    }
    $params = array_merge($params, $override);

    return $params ? '?' . http_build_query($params) : '';
}

/** Cari gedung sesuai filter. Hasil sudah dihidrasi (foto, fasilitas, jenis, layanan). */
function find_venues(array $filters, ?string $kategoriWajib = null): array
{
    $sql    = 'SELECT v.*, k.nama AS kategori
               FROM venues v
               JOIN kategori k ON k.id = v.kategori_id
               WHERE 1 = 1';
    $params = [];

    if ($filters['q'] !== '') {
        $sql .= ' AND v.nama LIKE :q';
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if ($filters['min'] > 0) {
        $sql .= ' AND v.harga_per_jam >= :min';
        $params['min'] = $filters['min'];
    }
    if ($filters['max'] < price_ceiling()) {
        $sql .= ' AND v.harga_per_jam <= :max';
        $params['max'] = $filters['max'];
    }
    if ($filters['kapasitas'] > 0) {
        $sql .= ' AND v.kapasitas >= :kapasitas';
        $params['kapasitas'] = $filters['kapasitas'];
    }
    if ($filters['kecamatan']) {
        $holders = [];
        foreach ($filters['kecamatan'] as $i => $value) {
            $holders[]        = ":kec$i";
            $params["kec$i"] = $value;
        }
        $sql .= ' AND v.kecamatan IN (' . implode(', ', $holders) . ')';
    }
    if ($filters['kategori']) {
        $holders = [];
        foreach ($filters['kategori'] as $i => $value) {
            $holders[]         = ":kat$i";
            $params["kat$i"] = $value;
        }
        $sql .= ' AND k.nama IN (' . implode(', ', $holders) . ')';
    }
    if ($filters['acara']) {
        $holders = [];
        foreach ($filters['acara'] as $i => $value) {
            $holders[]          = ":acr$i";
            $params["acr$i"] = $value;
        }
        $sql .= ' AND EXISTS (SELECT 1 FROM venue_jenis vj
                              JOIN jenis_acara ja ON ja.id = vj.jenis_acara_id
                              WHERE vj.venue_id = v.id
                                AND ja.nama IN (' . implode(', ', $holders) . '))';
    }
    if ($kategoriWajib) {
        $sql .= ' AND k.nama = :kategori_wajib';
        $params['kategori_wajib'] = $kategoriWajib;
    }

    $sql .= match ($filters['sort']) {
        'rendah'    => ' ORDER BY v.harga_per_jam ASC, v.id ASC',
        'tinggi'    => ' ORDER BY v.harga_per_jam DESC, v.id ASC',
        'kapasitas' => ' ORDER BY v.kapasitas DESC, v.id ASC',
        default     => ' ORDER BY v.id ASC',
    };

    $st = db()->prepare($sql);
    $st->execute($params);

    return hydrate_venues($st->fetchAll());
}

/** Ambil satu gedung lengkap. */
function get_venue(int $id): ?array
{
    $st = db()->prepare('SELECT v.*, k.nama AS kategori
                         FROM venues v JOIN kategori k ON k.id = v.kategori_id
                         WHERE v.id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    $rows = hydrate_venues([$row]);

    return $rows[0];
}

/** Ambil beberapa gedung berdasarkan daftar id (dipakai halaman Bandingkan & tray). */
function get_venues_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $in   = implode(', ', array_fill(0, count($ids), '?'));
    $st   = db()->prepare("SELECT v.*, k.nama AS kategori
                           FROM venues v JOIN kategori k ON k.id = v.kategori_id
                           WHERE v.id IN ($in)");
    $st->execute($ids);
    $rows = hydrate_venues($st->fetchAll());

    // Pertahankan urutan sesuai id yang diminta
    $byId  = array_column($rows, null, 'id');
    $hasil = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $hasil[] = $byId[$id];
        }
    }

    return $hasil;
}

/** Lengkapi baris venues dengan relasi (foto, fasilitas, jenis acara, layanan). */
function hydrate_venues(array $rows): array
{
    if (!$rows) {
        return [];
    }

    $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
    $in  = implode(', ', array_fill(0, count($ids), '?'));

    $foto = db()->prepare("SELECT venue_id, url FROM venue_foto
                           WHERE venue_id IN ($in) ORDER BY urutan");
    $foto->execute($ids);
    $fotoMap = [];
    foreach ($foto->fetchAll() as $r) {
        $fotoMap[(int) $r['venue_id']][] = $r['url'];
    }

    $fas = db()->prepare("SELECT vf.venue_id, f.nama
                          FROM venue_fasilitas vf JOIN fasilitas f ON f.id = vf.fasilitas_id
                          WHERE vf.venue_id IN ($in) ORDER BY f.urutan");
    $fas->execute($ids);
    $fasMap = [];
    foreach ($fas->fetchAll() as $r) {
        $fasMap[(int) $r['venue_id']][] = $r['nama'];
    }

    $jen = db()->prepare("SELECT vj.venue_id, j.nama
                          FROM venue_jenis vj JOIN jenis_acara j ON j.id = vj.jenis_acara_id
                          WHERE vj.venue_id IN ($in) ORDER BY j.urutan");
    $jen->execute($ids);
    $jenMap = [];
    foreach ($jen->fetchAll() as $r) {
        $jenMap[(int) $r['venue_id']][] = $r['nama'];
    }

    $lay = db()->prepare("SELECT venue_id, nama, harga, satuan FROM layanan
                          WHERE venue_id IN ($in) ORDER BY urutan, id");
    $lay->execute($ids);
    $layMap = [];
    foreach ($lay->fetchAll() as $r) {
        $layMap[(int) $r['venue_id']][] = [
            'nama'   => $r['nama'],
            'harga'  => (int) $r['harga'],
            'satuan' => $r['satuan'],
        ];
    }

    $hasil = [];
    foreach ($rows as $row) {
        $id                = (int) $row['id'];
        $row['id']         = $id;
        $row['kapasitas']  = (int) $row['kapasitas'];
        $row['harga_per_jam'] = (int) $row['harga_per_jam'];
        $row['minimal_jam']   = (int) $row['minimal_jam'];
        $row['kategori_id']   = (int) $row['kategori_id'];
        $row['kontak_demo']   = (bool) $row['kontak_demo'];
        $row['photos']        = $fotoMap[$id] ?? [];
        $row['fasilitas']     = $fasMap[$id] ?? [];
        $row['jenis_acara']   = $jenMap[$id] ?? [];
        $row['layanan']       = $layMap[$id] ?? [];
        $hasil[]              = $row;
    }

    return $hasil;
}

/* =====================================================================
   STATISTIK DASHBOARD
   ===================================================================== */

/** @return array{total:int, per_kategori:array<string,int>} */
function venue_stats(): array
{
    $total = (int) db()->query('SELECT COUNT(*) FROM venues')->fetchColumn();
    $rows  = db()->query('SELECT k.nama, COUNT(v.id) AS jumlah
                          FROM kategori k LEFT JOIN venues v ON v.kategori_id = k.id
                          GROUP BY k.id, k.nama ORDER BY k.urutan')->fetchAll();
    $per = [];
    foreach ($rows as $row) {
        $per[$row['nama']] = (int) $row['jumlah'];
    }

    return ['total' => $total, 'per_kategori' => $per];
}

/* =====================================================================
   CRUD GEDUNG
   ===================================================================== */

/**
 * Validasi input form gedung.
 *
 * @return array{data:array<string,mixed>, errors:array<string,string>}
 */
function validate_venue(array $post): array
{
    $fotoInput = $post['foto'] ?? [];
    $fotoInput = is_array($fotoInput) ? array_values($fotoInput) : [];
    while (count($fotoInput) < 4) {
        $fotoInput[] = '';
    }

    $data = [
        'nama'          => trim((string) ($post['nama'] ?? '')),
        'kategori_id'   => (int) ($post['kategori_id'] ?? 0),
        'kecamatan'     => trim((string) ($post['kecamatan'] ?? '')),
        'alamat'        => trim((string) ($post['alamat'] ?? '')),
        'kapasitas'     => (int) ($post['kapasitas'] ?? 0),
        'harga_per_jam' => (int) ($post['harga_per_jam'] ?? 0),
        'minimal_jam'   => (int) ($post['minimal_jam'] ?? 0),
        'kontak_nama'   => trim((string) ($post['kontak_nama'] ?? '')),
        'kontak_wa'     => preg_replace('/\D+/', '', (string) ($post['kontak_wa'] ?? '')) ?? '',
        'website_resmi' => trim((string) ($post['website_resmi'] ?? '')),
        'kontak_demo'   => isset($post['kontak_demo']) ? 1 : 0,
        'deskripsi'     => trim((string) ($post['deskripsi'] ?? '')),
        'jenis'         => array_values(array_filter(array_map('intval', (array) ($post['jenis'] ?? [])))),
        'fasilitas'     => array_values(array_filter(array_map('intval', (array) ($post['fasilitas'] ?? [])))),
        'foto'          => array_map(static fn ($v): string => trim((string) $v), $fotoInput),
        'layanan'       => [],
    ];

    // Layanan (baris berulang)
    $ln = (array) ($post['layanan_nama'] ?? []);
    $lh = (array) ($post['layanan_harga'] ?? []);
    $ls = (array) ($post['layanan_satuan'] ?? []);
    foreach ($ln as $i => $nama) {
        $nama = trim((string) $nama);
        if ($nama === '' && (string) ($lh[$i] ?? '') === '') {
            continue;
        }
        $data['layanan'][] = [
            'nama'   => $nama,
            'harga'  => (int) ($lh[$i] ?? 0),
            'satuan' => (($ls[$i] ?? 'per_jam') === 'sekali') ? 'sekali' : 'per_jam',
        ];
    }

    $errors = [];
    foreach (['nama', 'alamat', 'kontak_nama', 'deskripsi'] as $key) {
        if ($data[$key] === '') {
            $errors[$key] = 'Kolom ini wajib diisi.';
        }
    }
    if (!isset(masters()['Kategori'][$data['kategori_id']])) {
        $errors['kategori_id'] = 'Pilih kategori gedung.';
    }
    if (!in_array($data['kecamatan'], kecamatan_list(), true)) {
        $errors['kecamatan'] = 'Pilih kecamatan.';
    }
    foreach (['kapasitas', 'harga_per_jam', 'minimal_jam'] as $key) {
        if ($data[$key] <= 0) {
            $errors[$key] = 'Masukkan angka lebih dari 0.';
        }
    }
    if ($data['kontak_wa'] !== '' && !preg_match('/^\d{10,13}$/', $data['kontak_wa'])) {
        $errors['kontak_wa'] = 'Masukkan 10–13 digit angka tanpa spasi atau tanda +.';
    }
    if ($data['website_resmi'] !== '' && website_url($data['website_resmi']) === null) {
        $errors['website_resmi'] = 'Masukkan URL website yang valid dengan http:// atau https://.';
    }
    if (!$data['jenis']) {
        $errors['jenis'] = 'Pilih minimal satu jenis acara.';
    }
    foreach ($data['layanan'] as $layanan) {
        if ($layanan['nama'] === '' || $layanan['harga'] < 0) {
            $errors['layanan'] = 'Isi nama dan harga layanan yang valid.';
            break;
        }
    }
    foreach ($data['foto'] as $url) {
        if ($url === '') {
            continue;
        }
        $valid = preg_match('#^https?://\S+$#i', $url) || preg_match('/^[\w.-]+\.(jpe?g|png|webp)$/i', $url);
        if (!$valid) {
            $errors['foto'] = 'Gunakan nama foto contoh (mis. venue-1.jpg) atau URL http/https.';
            break;
        }
    }

    return ['data' => $data, 'errors' => $errors];
}

/** Simpan (insert/update) gedung beserta relasinya dalam satu transaksi. */
function venue_save(array $data, ?int $id = null): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($id === null) {
            $st = $pdo->prepare('INSERT INTO venues
                (nama, kategori_id, kecamatan, alamat, kapasitas, harga_per_jam, minimal_jam,
                 kontak_nama, kontak_wa, website_resmi, kontak_demo, deskripsi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([
                $data['nama'], $data['kategori_id'], $data['kecamatan'], $data['alamat'],
                $data['kapasitas'], $data['harga_per_jam'], $data['minimal_jam'],
                $data['kontak_nama'], $data['kontak_wa'], $data['website_resmi'],
                $data['kontak_demo'], $data['deskripsi'],
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $st = $pdo->prepare('UPDATE venues SET
                nama = ?, kategori_id = ?, kecamatan = ?, alamat = ?, kapasitas = ?,
                harga_per_jam = ?, minimal_jam = ?, kontak_nama = ?, kontak_wa = ?,
                website_resmi = ?, kontak_demo = ?, deskripsi = ?
                WHERE id = ?');
            $st->execute([
                $data['nama'], $data['kategori_id'], $data['kecamatan'], $data['alamat'],
                $data['kapasitas'], $data['harga_per_jam'], $data['minimal_jam'],
                $data['kontak_nama'], $data['kontak_wa'], $data['website_resmi'],
                $data['kontak_demo'], $data['deskripsi'], $id,
            ]);
            $pdo->prepare('DELETE FROM venue_foto WHERE venue_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM layanan WHERE venue_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM venue_fasilitas WHERE venue_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM venue_jenis WHERE venue_id = ?')->execute([$id]);
        }

        // Foto: simpan 4 baris (kosong -> pakai foto contoh berdasarkan urutan)
        $foto = $data['foto'];
        for ($i = 0; $i < 4; $i++) {
            $url = trim((string) ($foto[$i] ?? ''));
            if ($url === '') {
                $url = 'venue-' . ((($id - 1) + $i) % 8 + 1) . '.jpg';
            }
            $pdo->prepare('INSERT INTO venue_foto (venue_id, url, urutan) VALUES (?, ?, ?)')
                ->execute([$id, $url, $i + 1]);
        }

        // Fasilitas & jenis acara
        $insFas = $pdo->prepare('INSERT INTO venue_fasilitas (venue_id, fasilitas_id) VALUES (?, ?)');
        foreach ($data['fasilitas'] as $fid) {
            if (isset(masters()['Fasilitas'][$fid])) {
                $insFas->execute([$id, $fid]);
            }
        }
        $insJen = $pdo->prepare('INSERT INTO venue_jenis (venue_id, jenis_acara_id) VALUES (?, ?)');
        foreach ($data['jenis'] as $jid) {
            if (isset(masters()['Jenis Acara'][$jid])) {
                $insJen->execute([$id, $jid]);
            }
        }

        // Layanan tambahan
        $insLay = $pdo->prepare('INSERT INTO layanan (venue_id, nama, harga, satuan, urutan) VALUES (?, ?, ?, ?, ?)');
        foreach ($data['layanan'] as $i => $layanan) {
            $insLay->execute([$id, $layanan['nama'], $layanan['harga'], $layanan['satuan'], $i + 1]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $id;
}

function venue_delete(int $id): void
{
    db()->prepare('DELETE FROM venues WHERE id = ?')->execute([$id]);
}

/* =====================================================================
   CSV
   ===================================================================== */

/** Header kolom template CSV. */
function csv_columns(): array
{
    return [
        'nama', 'kategori', 'kecamatan', 'kapasitas', 'harga_per_jam', 'minimal_jam',
        'kontak_nama', 'kontak_wa', 'website_resmi',
    ];
}

/** Contoh isi template CSV (1 baris). */
function csv_template_content(): string
{
    $contoh = [
        'Gedung Contoh', 'Aula', 'Coblong', '300', '500000', '2',
        'Pengelola Gedung Contoh (contoh)', '6280000000100',
        'https://example.com/?gedung=Gedung%20Contoh',
    ];
    $lines = [implode(',', csv_columns()), implode(',', array_map(
        static fn (string $v): string => str_contains($v, ',') ? '"' . $v . '"' : $v,
        $contoh
    ))];

    return implode("\n", $lines) . "\n";
}

/**
 * Parsing + validasi CSV. Mengembalikan baris beserta status validasi.
 *
 * @return array<int, array{data:array<string,string>, errors:array<int,string>}>
 */
function parse_csv(string $content): array
{
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $lines   = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
    if (count($lines) < 2) {
        return [];
    }

    $header = array_map(static fn ($h) => strtolower(trim((string) $h)), str_getcsv(array_shift($lines)));
    $wajib  = ['nama', 'kategori', 'kecamatan', 'kapasitas', 'harga_per_jam', 'minimal_jam'];
    foreach ($wajib as $kolom) {
        if (!in_array($kolom, $header, true)) {
            return [];
        }
    }

    $hasil = [];
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $cells = str_getcsv($line);
        $row   = [];
        foreach ($header as $i => $kolom) {
            $row[$kolom] = trim((string) ($cells[$i] ?? ''));
        }

        $errors = [];
        if ($row['nama'] === '') {
            $errors[] = 'Nama gedung wajib diisi';
        }
        if (master_id_by_name('Kategori', $row['kategori']) === null) {
            $errors[] = 'Kategori tidak dikenal';
        }
        if (!in_array($row['kecamatan'], kecamatan_list(), true)) {
            $errors[] = 'Kecamatan tidak dikenal';
        }
        if ((int) $row['kapasitas'] <= 0) {
            $errors[] = 'Kapasitas harus lebih dari 0';
        }
        if ((int) $row['harga_per_jam'] <= 0) {
            $errors[] = 'Harga per jam harus lebih dari 0';
        }
        if ((int) $row['minimal_jam'] <= 0) {
            $errors[] = 'Minimal jam harus lebih dari 0';
        }
        if (($row['kontak_wa'] ?? '') !== '' && !preg_match('/^\d{10,13}$/', preg_replace('/\D+/', '', $row['kontak_wa']) ?? '')) {
            $errors[] = 'Nomor WhatsApp harus 10–13 digit';
        }

        $hasil[] = ['data' => $row, 'errors' => $errors];
    }

    return $hasil;
}

/** Simpan satu baris CSV yang sudah valid menjadi gedung baru. */
function import_csv_row(array $row): int
{
    $kategoriId = master_id_by_name('Kategori', $row['kategori']) ?? 0;
    $data       = [
        'nama'          => $row['nama'],
        'kategori_id'   => $kategoriId,
        'kecamatan'     => $row['kecamatan'],
        'alamat'        => $row['alamat'] ?? (($row['nama'] ?? '') . ', ' . $row['kecamatan'] . ', Kota Bandung'),
        'kapasitas'     => (int) $row['kapasitas'],
        'harga_per_jam' => (int) $row['harga_per_jam'],
        'minimal_jam'   => (int) $row['minimal_jam'],
        'kontak_nama'   => $row['kontak_nama'] ?? '',
        'kontak_wa'     => preg_replace('/\D+/', '', $row['kontak_wa'] ?? '') ?? '',
        'website_resmi' => $row['website_resmi'] ?? '',
        'kontak_demo'   => 1,
        'deskripsi'     => $row['deskripsi'] ?? (
            ($row['nama'] ?? 'Gedung') . ' adalah pilihan ruang serbaguna di ' . $row['kecamatan']
            . ', Bandung. Hubungi pengelola untuk informasi ketersediaan dan ketentuan sewa.'
        ),
        'jenis'         => array_values(array_filter([
            master_id_by_name('Jenis Acara', 'Pernikahan'),
            master_id_by_name('Jenis Acara', 'Seminar'),
        ])),
        'fasilitas'     => array_values(array_filter([
            master_id_by_name('Fasilitas', 'AC'),
            master_id_by_name('Fasilitas', 'Parkir'),
            master_id_by_name('Fasilitas', 'Toilet'),
            master_id_by_name('Fasilitas', 'Wifi'),
        ])),
        'foto'          => ['', '', '', ''],
        'layanan'       => [
            ['nama' => 'Sound System', 'harga' => 150000, 'satuan' => 'per_jam'],
            ['nama' => 'Proyektor', 'harga' => 100000, 'satuan' => 'per_jam'],
        ],
    ];

    return venue_save($data);
}
