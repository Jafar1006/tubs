<?php
/**
 * Form tambah / edit gedung (CREATE & UPDATE).
 * Validasi dilakukan di server (includes/repository.php::validate_venue) dan
 * dibantu JavaScript untuk pengalaman pengisian.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

require_login();

$id     = (int) ($_GET['id'] ?? 0);
$venue  = $id > 0 ? get_venue($id) : null;
$isEdit = $venue !== null;

if ($id > 0 && $venue === null) {
    flash('Gedung tidak ditemukan.', 'error');
    redirect('admin/gedung.php');
}

$masters   = masters();
$kecamatan = kecamatan_list();
$errors    = [];

/** Nilai awal form. */
$data = [
    'nama'          => '',
    'kategori_id'   => array_key_first($masters['Kategori']) ?? 0,
    'kecamatan'     => $kecamatan[0],
    'alamat'        => '',
    'kapasitas'     => 100,
    'harga_per_jam' => 300000,
    'minimal_jam'   => 2,
    'kontak_nama'   => '',
    'kontak_wa'     => '',
    'website_resmi' => '',
    'kontak_demo'   => 1,
    'deskripsi'     => '',
    'jenis'         => [],
    'fasilitas'     => [],
    'foto'          => ['venue-1.jpg', 'venue-2.jpg', 'venue-3.jpg', 'venue-4.jpg'],
    'layanan'       => [],
];

if ($isEdit) {
    $namaKeId = static fn (string $key, array $nama): array => array_values(array_filter(array_map(
        static fn ($n): ?int => master_id_by_name($key, $n),
        $nama
    )));

    $data = [
        'nama'          => (string) $venue['nama'],
        'kategori_id'   => (int) $venue['kategori_id'],
        'kecamatan'     => (string) $venue['kecamatan'],
        'alamat'        => (string) $venue['alamat'],
        'kapasitas'     => (int) $venue['kapasitas'],
        'harga_per_jam' => (int) $venue['harga_per_jam'],
        'minimal_jam'   => (int) $venue['minimal_jam'],
        'kontak_nama'   => (string) $venue['kontak_nama'],
        'kontak_wa'     => (string) $venue['kontak_wa'],
        'website_resmi' => (string) $venue['website_resmi'],
        'kontak_demo'   => !empty($venue['kontak_demo']) ? 1 : 0,
        'deskripsi'     => (string) $venue['deskripsi'],
        'jenis'         => $namaKeId('Jenis Acara', $venue['jenis_acara']),
        'fasilitas'     => $namaKeId('Fasilitas', $venue['fasilitas']),
        'foto'          => array_pad($venue['photos'], 4, ''),
        'layanan'       => $venue['layanan'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $hasil  = validate_venue($_POST);
    $data   = $hasil['data'];
    $errors = $hasil['errors'];

    if (!$errors) {
        $savedId = venue_save($data, $isEdit ? $id : null);
        flash('Data gedung tersimpan');
        redirect('admin/gedung.php');
    }
    flash('Periksa kembali kolom yang ditandai sebelum menyimpan.', 'error');
}

/** Tampilkan pesan error untuk satu field. */
$err = static fn (string $key): string =>
    isset($errors[$key]) ? ' <span class="error">' . e($errors[$key]) . '</span>' : '';
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true"' : '';

$adminSection = 'form';
$pageTitle    = $isEdit ? 'Edit gedung' : 'Tambah gedung';
include __DIR__ . '/../partials/admin-header.php';
?>

<div class="page-heading">
  <div>
    <a class="text-btn" href="<?= e(url('admin/gedung.php')) ?>">← Kembali ke kelola gedung</a>
    <h1><?= $isEdit ? 'Edit gedung' : 'Tambah gedung' ?></h1>
    <p>Lengkapi informasi yang akan ditampilkan pada katalog.</p>
  </div>
</div>

<form class="admin-panel panel-padding venue-form" method="post" novalidate
      action="<?= e(url('admin/gedung-form.php' . ($isEdit ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>

  <h2>Informasi gedung</h2>
  <div class="form-grid">
    <label class="field">
      Nama gedung *
      <input type="text" name="nama" value="<?= e($data['nama']) ?>" required<?= $invalid('nama') ?>>
      <?= $err('nama') ?>
    </label>

    <label class="field">
      Kategori *
      <select name="kategori_id"<?= $invalid('kategori_id') ?>>
        <option value="">Pilih kategori</option>
        <?php foreach ($masters['Kategori'] as $kid => $nama): ?>
          <option value="<?= (int) $kid ?>" <?= (int) $data['kategori_id'] === (int) $kid ? 'selected' : '' ?>>
            <?= e($nama) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= $err('kategori_id') ?>
    </label>

    <label class="field">
      Kecamatan *
      <select name="kecamatan"<?= $invalid('kecamatan') ?>>
        <option value="">Pilih kecamatan</option>
        <?php foreach ($kecamatan as $kec): ?>
          <option value="<?= e($kec) ?>" <?= $data['kecamatan'] === $kec ? 'selected' : '' ?>><?= e($kec) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $err('kecamatan') ?>
    </label>

    <label class="field">
      Alamat lengkap *
      <input type="text" name="alamat" value="<?= e($data['alamat']) ?>" required<?= $invalid('alamat') ?>>
      <?= $err('alamat') ?>
    </label>

    <label class="field">
      Kapasitas (orang) *
      <input type="number" name="kapasitas" min="1" value="<?= (int) $data['kapasitas'] ?>" required<?= $invalid('kapasitas') ?>>
      <?= $err('kapasitas') ?>
    </label>

    <label class="field">
      Harga per jam (Rp) *
      <input type="number" name="harga_per_jam" min="1" value="<?= (int) $data['harga_per_jam'] ?>" required<?= $invalid('harga_per_jam') ?>>
      <?= $err('harga_per_jam') ?>
    </label>

    <label class="field">
      Minimal sewa (jam) *
      <input type="number" name="minimal_jam" min="1" max="24" value="<?= (int) $data['minimal_jam'] ?>" required<?= $invalid('minimal_jam') ?>>
      <?= $err('minimal_jam') ?>
    </label>

    <label class="field">
      Nama pengelola atau kontak resmi *
      <input type="text" name="kontak_nama" value="<?= e($data['kontak_nama']) ?>" required<?= $invalid('kontak_nama') ?>>
      <?= $err('kontak_nama') ?>
    </label>

    <label class="field">
      Nomor WhatsApp resmi (opsional)
      <input type="tel" name="kontak_wa" inputmode="numeric" value="<?= e($data['kontak_wa']) ?>"<?= $invalid('kontak_wa') ?>>
      <?= $err('kontak_wa') ?>
    </label>

    <label class="field">
      URL website resmi (opsional)
      <input type="url" name="website_resmi" value="<?= e($data['website_resmi']) ?>"<?= $invalid('website_resmi') ?>>
      <?= $err('website_resmi') ?>
    </label>

    <div class="span-2">
      <label class="checkbox-row">
        <input type="checkbox" name="kontak_demo" value="1" <?= !empty($data['kontak_demo']) ? 'checked' : '' ?>>
        Informasi kontak adalah data contoh prototipe
      </label>
      <p class="secondary small contact-notice">
        Kosongkan nomor WhatsApp atau URL website untuk menghapusnya dari halaman publik. Data
        contoh ditandai agar tidak dianggap sebagai kontak resmi terverifikasi.
      </p>
    </div>

    <label class="field span-2">
      Deskripsi *
      <textarea name="deskripsi" required<?= $invalid('deskripsi') ?>><?= e($data['deskripsi']) ?></textarea>
      <?= $err('deskripsi') ?>
    </label>
  </div>

  <section class="form-section">
    <h3>Jenis acara *</h3>
    <div class="form-checkboxes">
      <?php foreach ($masters['Jenis Acara'] as $jid => $nama): ?>
        <label class="checkbox-row">
          <input type="checkbox" name="jenis[]" value="<?= (int) $jid ?>"
            <?= in_array((int) $jid, array_map('intval', $data['jenis']), true) ? 'checked' : '' ?>>
          <?= e($nama) ?>
        </label>
      <?php endforeach; ?>
    </div>
    <?= $err('jenis') ?>
  </section>

  <section class="form-section">
    <h3>Fasilitas gratis</h3>
    <div class="form-checkboxes">
      <?php foreach ($masters['Fasilitas'] as $fid => $nama): ?>
        <label class="checkbox-row">
          <input type="checkbox" name="fasilitas[]" value="<?= (int) $fid ?>"
            <?= in_array((int) $fid, array_map('intval', $data['fasilitas']), true) ? 'checked' : '' ?>>
          <?= e($nama) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="form-section">
    <div class="panel-heading">
      <h3>Layanan tambahan</h3>
      <button type="button" class="btn outline" id="add-service">+ Tambah layanan</button>
    </div>
    <div id="service-rows">
      <?php foreach ($data['layanan'] as $layanan): ?>
        <div class="service-form-row">
          <label class="field">
            Nama layanan
            <input type="text" name="layanan_nama[]" value="<?= e($layanan['nama']) ?>">
          </label>
          <label class="field">
            Harga (Rp)
            <input type="number" name="layanan_harga[]" min="0" value="<?= (int) $layanan['harga'] ?>">
          </label>
          <label class="field">
            Satuan
            <select name="layanan_satuan[]">
              <option value="per_jam" <?= $layanan['satuan'] === 'per_jam' ? 'selected' : '' ?>>Per jam</option>
              <option value="sekali" <?= $layanan['satuan'] === 'sekali' ? 'selected' : '' ?>>Sekali</option>
            </select>
          </label>
          <button class="icon-btn danger" type="button" aria-label="Hapus layanan" data-remove-service>
            <?= icon('close') ?>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
    <?= $err('layanan') ?>
  </section>

  <section class="form-section">
    <h3>Foto gedung</h3>
    <p class="secondary small">
      Gunakan URL foto untuk 4 sudut gedung. Foto contoh sudah disediakan (venue-1.jpg … venue-8.jpg).
    </p>
    <div class="form-grid">
      <?php for ($i = 0; $i < 4; $i++): ?>
        <label class="field">
          Foto <?= $i + 1 ?>
          <input type="text" name="foto[]" value="<?= e($data['foto'][$i] ?? '') ?>"
                 placeholder="venue-<?= $i + 1 ?>.jpg atau https://…">
        </label>
      <?php endfor; ?>
    </div>
    <?= $err('foto') ?>
  </section>

  <?php if ($errors): ?>
    <p class="error">Periksa kembali kolom yang ditandai sebelum menyimpan.</p>
  <?php endif; ?>

  <div class="button-row end">
    <a class="btn outline" href="<?= e(url('admin/gedung.php')) ?>">Batal</a>
    <button class="btn green" type="submit">
      Simpan gedung
      <?= icon('check', 17) ?>
    </button>
  </div>
</form>

<template id="service-row-template">
  <div class="service-form-row">
    <label class="field">
      Nama layanan
      <input type="text" name="layanan_nama[]" value="">
    </label>
    <label class="field">
      Harga (Rp)
      <input type="number" name="layanan_harga[]" min="0" value="0">
    </label>
    <label class="field">
      Satuan
      <select name="layanan_satuan[]">
        <option value="per_jam">Per jam</option>
        <option value="sekali">Sekali</option>
      </select>
    </label>
    <button class="icon-btn danger" type="button" aria-label="Hapus layanan" data-remove-service>
      <?= icon('close') ?>
    </button>
  </div>
</template>

<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
