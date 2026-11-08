<?php
/**
 * Layout area admin (sidebar kiri + header atas).
 *
 * Variabel: $adminSection (dashboard|gedung|form|impor|master), $pageTitle
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/icons.php';

$adminSection = $adminSection ?? 'dashboard';
$pageTitle    = $pageTitle ?? 'Admin';
$flashes      = flash_set();

$menu = [
    ['dashboard', 'grid',     'Dashboard',     'admin/index.php'],
    ['gedung',    'building', 'Kelola gedung', 'admin/gedung.php'],
    ['impor',     'upload',   'Impor CSV',     'admin/impor.php'],
    ['master',    'filter',   'Master data',   'admin/master.php'],
];
$judul = 'Dashboard';
foreach ($menu as [$key, , $label]) {
    if ($key === $adminSection) {
        $judul = $label;
    }
}
if ($adminSection === 'form') {
    $judul = 'Form gedung';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($pageTitle) ?> · Admin <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
  <div class="toasts" id="toasts" aria-live="polite" aria-atomic="true">
    <?php foreach ($flashes as $flash): ?>
      <div class="toast" data-autohide="1">
        <?= icon($flash['tipe'] === 'error' ? 'close' : 'check') ?>
        <?= e($flash['pesan']) ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="admin-layout">
    <aside class="admin-sidebar">
      <?= logo(url('index.php')) ?>
      <span class="admin-eyebrow">AREA PENGELOLA</span>
      <nav aria-label="Navigasi admin">
        <?php foreach ($menu as [$key, $ico, $label, $path]): ?>
          <a class="<?= $adminSection === $key ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
            <?= icon($ico) ?>
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-bottom">
        <a href="<?= e(url('index.php')) ?>"><?= icon('arrow') ?> Lihat katalog publik</a>
        <a href="<?= e(url('admin/logout.php')) ?>"><?= icon('logout') ?> Keluar</a>
      </div>
    </aside>

    <div class="admin-main">
      <header class="admin-top">
        <span>
          <?= e(APP_NAME) ?>
          <span class="secondary">/ <?= e($judul) ?></span>
        </span>
        <span class="admin-avatar" aria-hidden="true">
          <?= e(mb_substr((string) (current_admin()['nama'] ?? 'A'), 0, 1)) ?>
        </span>
      </header>
      <main class="admin-content">
