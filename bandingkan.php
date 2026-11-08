<?php
/**
 * Halaman Bandingkan: membandingkan 2–3 gedung dalam satu tabel.
 * Daftar id berasal dari compare tray (localStorage) lalu dikirim sebagai ?ids=1,2,3
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/partials/icons.php';
require_once __DIR__ . '/partials/contact.php';

$ids = [];
if (isset($_GET['ids'])) {
    foreach (explode(',', (string) $_GET['ids']) as $raw) {
        $id = (int) trim($raw);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
}
$ids    = array_slice(array_values(array_unique($ids)), 0, 3);
$venues = get_venues_by_ids($ids);

$masters = masters();
$pageTitle  = 'Bandingkan gedung';
$activePage = 'bandingkan';

include __DIR__ . '/partials/header.php';

if (count($venues) < 2): ?>
  <main class="container">
    <div class="empty">
      <span class="empty-icon"><?= icon('building', 40) ?></span>
      <h2>Pilih minimal 2 gedung untuk dibandingkan</h2>
      <p>Tambahkan gedung dari katalog, lalu bandingkan harga, kapasitas, dan fasilitasnya.</p>
      <a class="btn green" href="<?= e(url('index.php')) ?>">
        Ke katalog
        <?= icon('arrow', 16) ?>
      </a>
    </div>
  </main>
  <?php
  include __DIR__ . '/partials/footer.php';
  exit;
endif;

// Nilai terbaik untuk penyorotan
$kapasitasMax = max(array_column($venues, 'kapasitas'));
$hargaMin     = min(array_column($venues, 'harga_per_jam'));
$sewaMin      = min(array_map(
    static fn (array $v): int => $v['harga_per_jam'] * $v['minimal_jam'],
    $venues
));

/** Baris tabel: label + callback nilai + callback "terbaik". */
$rows = [];

$rows[] = ['label' => 'Kategori', 'value' => static fn (array $v): string => e($v['kategori'])];
$rows[] = ['label' => 'Kecamatan', 'value' => static fn (array $v): string => e($v['kecamatan'])];
$rows[] = [
    'label' => 'Kapasitas',
    'value' => static fn (array $v): string => e(ribuan($v['kapasitas'])) . ' orang',
    'best'  => static fn (array $v): bool => $v['kapasitas'] === $kapasitasMax,
];
$rows[] = [
    'label' => 'Harga per jam',
    'value' => static fn (array $v): string => e(rupiah($v['harga_per_jam'])) . '/jam',
    'best'  => static fn (array $v): bool => $v['harga_per_jam'] === $hargaMin,
];
$rows[] = ['label' => 'Minimal jam', 'value' => static fn (array $v): string => (int) $v['minimal_jam'] . ' jam'];
$rows[] = [
    'label' => 'Harga minimal sewa',
    'value' => static fn (array $v): string => e(rupiah($v['harga_per_jam'] * $v['minimal_jam'])),
    'best'  => static fn (array $v): bool => ($v['harga_per_jam'] * $v['minimal_jam']) === $sewaMin,
];
$rows[] = [
    'label' => 'Jenis acara',
    'value' => static fn (array $v): string => e(implode(', ', $v['jenis_acara'])),
];

foreach ($masters['Fasilitas'] as $fasilitas) {
    $rows[] = [
        'label' => $fasilitas,
        'value' => static function (array $v) use ($fasilitas): string {
            $ada = in_array($fasilitas, $v['fasilitas'], true);

            return '<span class="' . ($ada ? 'yes' : 'no') . '">' . ($ada ? '✓' : '✗') . '</span>';
        },
    ];
}

$rows[] = [
    'label' => 'Layanan tambahan',
    'value' => static function (array $v): string {
        if (!$v['layanan']) {
            return '<p class="secondary small">Belum ada layanan tambahan.</p>';
        }
        $html = '';
        foreach ($v['layanan'] as $layanan) {
            $html .= '<p class="compare-service">' . e($layanan['nama'])
                . '<strong>' . e(rupiah($layanan['harga'])) . '/'
                . ($layanan['satuan'] === 'per_jam' ? 'jam' : 'sekali')
                . '</strong></p>';
        }

        return $html;
    },
];

$rows[] = [
    'label' => 'Informasi Kontak & Website Resmi',
    'value' => static function (array $v): string {
        $html  = '<div class="comparison-contact">';
        $html .= '<p>' . e($v['kontak_nama'] !== '' ? $v['kontak_nama'] : 'Nama pengelola belum tersedia') . '</p>';
        $html .= '<p class="secondary small">' . e($v['kontak_wa'] !== '' ? $v['kontak_wa'] : 'Nomor WhatsApp belum tersedia') . '</p>';
        $html .= '<p class="secondary small">' . e($v['website_resmi'] !== '' ? $v['website_resmi'] : 'Website resmi belum tersedia') . '</p>';
        $html .= contact_notice($v);
        $html .= contact_actions($v);
        $html .= '</div>';

        return $html;
    },
];
?>
<main class="container comparison-page">
  <a class="back-btn" id="back-btn" href="<?= e(url('index.php')) ?>">
    <?= icon('arrowLeft', 17) ?>
    Kembali
  </a>

  <div class="eyebrow muted">PILIH DENGAN LEBIH YAKIN</div>
  <h1>Bandingkan gedung</h1>
  <p class="secondary">Semua informasi dalam satu tampilan. Temukan ruang yang paling sesuai untuk Anda.</p>
  <p class="best-key"><span></span> Nilai terbaik pada setiap kriteria</p>

  <div class="table-scroll">
    <table class="comparison-table">
      <thead>
        <tr>
          <th>Informasi gedung</th>
          <?php foreach ($venues as $v): ?>
            <th>
              <button type="button" class="remove-compare" data-remove-compare="<?= (int) $v['id'] ?>">
                Hapus ×
              </button>
              <img src="<?= e(foto_url($v['photos'][0] ?? '')) ?>" alt="<?= e($v['nama']) ?>"
                   width="240" height="120" loading="lazy">
              <h3><?= e($v['nama']) ?></h3>
              <a class="btn outline full" href="<?= e(url('detail.php?id=' . $v['id'])) ?>">
                Lihat Detail
                <?= icon('arrow', 15) ?>
              </a>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <th><?= e($row['label']) ?></th>
            <?php foreach ($venues as $v): ?>
              <?php $best = isset($row['best']) && $row['best']($v); ?>
              <td class="<?= $best ? 'best-value' : '' ?>"><?= $row['value']($v) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
