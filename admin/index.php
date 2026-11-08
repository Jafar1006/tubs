<?php
/**
 * Dashboard admin: statistik gedung + tabel gedung terbaru.
 * Semua angka dihitung langsung dari database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

require_login();

$stats  = venue_stats();
$masters = masters();

$latest = db()->query('SELECT v.*, k.nama AS kategori
                       FROM venues v JOIN kategori k ON k.id = v.kategori_id
                       ORDER BY v.id DESC LIMIT 5')->fetchAll();

$kartu = [
    ['title' => 'Total Gedung', 'count' => $stats['total'], 'to' => 'admin/gedung.php', 'icon' => 'building'],
];
foreach ($masters['Kategori'] as $nama) {
    $kartu[] = [
        'title' => 'Total ' . $nama,
        'count' => $stats['per_kategori'][$nama] ?? 0,
        'to'    => 'admin/gedung.php?kategori=' . rawurlencode($nama),
        'icon'  => str_contains($nama, 'Open') ? 'leaf' : 'building',
    ];
}
$kartu = array_slice($kartu, 0, 4);

$adminSection = 'dashboard';
$pageTitle    = 'Dashboard';
include __DIR__ . '/../partials/admin-header.php';
?>

<div class="page-heading">
  <div>
    <div class="eyebrow muted">RINGKASAN AKTIVITAS</div>
    <h1>Dashboard</h1>
    <p>Pantau informasi gedung dan kategori Anda di satu tempat.</p>
  </div>
  <a class="btn green" href="<?= e(url('admin/gedung-form.php')) ?>">+ Tambah gedung</a>
</div>

<div class="stat-grid">
  <?php foreach ($kartu as $k): ?>
    <a class="stat-card" href="<?= e(url($k['to'])) ?>">
      <span><?= icon($k['icon']) ?></span>
      <p><?= e($k['title']) ?></p>
      <strong><?= (int) $k['count'] ?></strong>
      <?= icon('arrow', 17) ?>
    </a>
  <?php endforeach; ?>
</div>

<section class="admin-panel">
  <div class="panel-heading">
    <h2>Gedung terbaru</h2>
    <a class="text-btn" href="<?= e(url('admin/gedung.php')) ?>">Lihat semua →</a>
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
        <?php foreach ($latest as $v): ?>
          <tr>
            <td><strong><?= e($v['nama']) ?></strong></td>
            <td><?= e($v['kategori']) ?></td>
            <td><?= e($v['kecamatan']) ?></td>
            <td><?= e(ribuan((int) $v['kapasitas'])) ?> orang</td>
            <td class="nowrap"><?= e(rupiah((int) $v['harga_per_jam'])) ?></td>
            <td>
              <a class="text-btn" href="<?= e(url('admin/gedung-form.php?id=' . (int) $v['id'])) ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$latest): ?>
      <p class="table-empty">Belum ada gedung. Tambahkan gedung untuk menampilkan data.</p>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
