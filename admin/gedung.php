<?php
/**
 * Kelola gedung: daftar, pencarian, filter kategori, edit, dan hapus (DELETE).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

require_login();

// ---- Hapus gedung (POST + CSRF) -------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $venue = $id > 0 ? get_venue($id) : null;
    if ($venue === null) {
        flash('Gedung tidak ditemukan.', 'error');
    } else {
        venue_delete($id);
        flash('Gedung berhasil dihapus');
    }
    redirect('admin/gedung.php');
}

$q        = trim((string) ($_GET['q'] ?? ''));
$kategori = trim((string) ($_GET['kategori'] ?? ''));
$masters  = masters();
if ($kategori !== '' && !isset(array_flip($masters['Kategori'])[$kategori])) {
    $kategori = '';
}

$venues = find_venues([
    'q'         => $q,
    'min'       => 0,
    'max'       => price_ceiling(),
    'kapasitas' => 0,
    'kecamatan' => [],
    'kategori'  => $kategori !== '' ? [$kategori] : [],
    'acara'     => [],
    'sort'      => 'rekomendasi',
]);

$adminSection = 'gedung';
$pageTitle    = 'Kelola gedung';
include __DIR__ . '/../partials/admin-header.php';
?>

<div class="page-heading">
  <div>
    <h1>Kelola gedung</h1>
    <p><?= count($venues) ?> gedung tersedia di katalog publik.</p>
  </div>
  <a class="btn green" href="<?= e(url('admin/gedung-form.php')) ?>">+ Tambah gedung</a>
</div>

<section class="admin-panel">
  <div class="panel-heading">
    <form class="admin-search" method="get" action="<?= e(url('admin/gedung.php')) ?>">
      <?= icon('search', 18) ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama gedung..."
             aria-label="Cari nama gedung" autocomplete="off">
      <?php if ($kategori !== ''): ?>
        <input type="hidden" name="kategori" value="<?= e($kategori) ?>">
      <?php endif; ?>
    </form>
    <form method="get" action="<?= e(url('admin/gedung.php')) ?>" class="inline-select">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <select name="kategori" aria-label="Filter kategori gedung" onchange="this.form.submit()">
        <option value="">Semua kategori</option>
        <?php foreach ($masters['Kategori'] as $nama): ?>
          <option value="<?= e($nama) ?>" <?= $kategori === $nama ? 'selected' : '' ?>><?= e($nama) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn outline" type="submit">Terapkan</button></noscript>
    </form>
  </div>

  <div class="table-scroll">
    <table class="data-table">
      <thead>
        <tr>
          <th>Nama gedung</th>
          <th>Kategori</th>
          <th>Kecamatan</th>
          <th>Kapasitas</th>
          <th>Harga/jam</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($venues as $v): ?>
          <tr>
            <td>
              <div class="table-venue">
                <img src="<?= e(foto_url($v['photos'][0] ?? '')) ?>" alt="" width="38" height="38" loading="lazy">
                <strong><?= e($v['nama']) ?></strong>
              </div>
            </td>
            <td><?= e($v['kategori']) ?></td>
            <td><?= e($v['kecamatan']) ?></td>
            <td><?= e(ribuan($v['kapasitas'])) ?> orang</td>
            <td class="nowrap"><?= e(rupiah($v['harga_per_jam'])) ?></td>
            <td>
              <div class="button-row">
                <a class="text-btn" href="<?= e(url('admin/gedung-form.php?id=' . $v['id'])) ?>">Edit</a>
                <button type="button" class="text-btn danger"
                        data-delete-venue="<?= (int) $v['id'] ?>"
                        data-delete-nama="<?= e($v['nama']) ?>">Hapus</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$venues): ?>
      <p class="table-empty">Tidak ada gedung yang cocok dengan pencarian.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Modal konfirmasi hapus -->
<div class="overlay" id="delete-modal" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-title">
    <header class="modal-head">
      <h2>Hapus gedung</h2>
      <button class="icon-btn" type="button" data-close-modal aria-label="Tutup"><?= icon('close') ?></button>
    </header>
    <form class="modal-body" method="post" action="<?= e(url('admin/gedung.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="hapus">
      <input type="hidden" name="id" value="" data-delete-id>
      <h3 id="delete-title">Hapus <span data-delete-name>gedung ini</span>?</h3>
      <p class="secondary">Data tidak bisa dikembalikan.</p>
      <div class="button-row end">
        <button class="btn outline" type="button" data-close-modal>Batal</button>
        <button class="btn red" type="submit">Hapus gedung</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
