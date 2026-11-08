<?php
/**
 * Master data: kelola kategori, fasilitas, dan jenis acara.
 * Perubahan di sini langsung menjadi pilihan pada form gedung dan filter publik.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

require_login();

$tabKeys = array_keys(master_tables());
$tab     = (string) ($_GET['tab'] ?? 'Kategori');
if (!in_array($tab, $tabKeys, true)) {
    $tab = 'Kategori';
}
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $kembali = 'admin/master.php?tab=' . rawurlencode((string) ($_POST['tab'] ?? $tab));

    if ($action === 'tambah') {
        $hasil = master_add((string) ($_POST['tab'] ?? $tab), (string) ($_POST['nama'] ?? ''));
        flash($hasil['message'], $hasil['ok'] ? 'sukses' : 'error');
    } elseif ($action === 'simpan') {
        $hasil = master_rename(
            (string) ($_POST['tab'] ?? $tab),
            (int) ($_POST['id'] ?? 0),
            (string) ($_POST['nama'] ?? '')
        );
        flash($hasil['message'], $hasil['ok'] ? 'sukses' : 'error');
    } elseif ($action === 'hapus') {
        $hasil = master_delete((string) ($_POST['tab'] ?? $tab), (int) ($_POST['id'] ?? 0));
        flash($hasil['message'], $hasil['ok'] ? 'sukses' : 'error');
    }
    redirect($kembali);
}

$items = masters()[$tab] ?? [];

$adminSection = 'master';
$pageTitle    = 'Master data';
include __DIR__ . '/../partials/admin-header.php';
?>

<div class="page-heading">
  <div>
    <h1>Master data</h1>
    <p>Atur pilihan kategori, fasilitas, dan jenis acara untuk seluruh platform.</p>
  </div>
</div>

<section class="admin-panel master-panel">
  <div class="tabs" role="tablist">
    <?php foreach ($tabKeys as $key): ?>
      <a class="<?= $tab === $key ? 'active' : '' ?>" role="tab"
         aria-selected="<?= $tab === $key ? 'true' : 'false' ?>"
         href="<?= e(url('admin/master.php?tab=' . rawurlencode($key))) ?>"><?= e($key) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="panel-padding">
    <form class="inline-add" method="post" action="<?= e(url('admin/master.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="tambah">
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <input type="text" name="nama" placeholder="Nama <?= e(mb_strtolower($tab)) ?> baru" required
             aria-label="Nama <?= e(mb_strtolower($tab)) ?> baru">
      <button type="submit" class="btn green">+ Tambah</button>
    </form>

    <?php foreach ($items as $id => $nama): ?>
      <?php if ($editId === (int) $id): ?>
        <form class="master-row" method="post" action="<?= e(url('admin/master.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="simpan">
          <input type="hidden" name="tab" value="<?= e($tab) ?>">
          <input type="hidden" name="id" value="<?= (int) $id ?>">
          <input type="text" name="nama" value="<?= e($nama) ?>" aria-label="Ubah nama" required>
          <button class="text-btn" type="submit">Simpan</button>
          <a class="text-btn" href="<?= e(url('admin/master.php?tab=' . rawurlencode($tab))) ?>">Batal</a>
        </form>
      <?php else: ?>
        <div class="master-row">
          <span><?= e($nama) ?></span>
          <a class="text-btn" href="<?= e(url('admin/master.php?tab=' . rawurlencode($tab) . '&edit=' . (int) $id)) ?>">Edit</a>
          <form method="post" action="<?= e(url('admin/master.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <button class="text-btn danger" type="submit">Hapus</button>
          </form>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!$items): ?>
      <p class="table-empty">Belum ada data. Tambahkan lewat kolom di atas.</p>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
