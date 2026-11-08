<?php
/**
 * Header halaman publik: <head>, navbar, dan wadah toast.
 *
 * Variabel opsional sebelum include:
 *   $pageTitle  (string)  judul tab browser
 *   $activePage ('katalog' | 'bandingkan' | 'detail' | '')
 *   $bodyClass  (string)  class tambahan untuk <body>
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/icons.php';

$pageTitle  = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? '';
$bodyClass  = $bodyClass ?? '';
$flashes    = flash_set();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Gedung Inn — temukan, bandingkan, dan estimasi biaya gedung serbaguna di Bandung.">
  <meta name="theme-color" content="#F7F6F2">
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <link rel="icon" href="<?= e(asset('img/venue-1.jpg')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
  <a class="skip-link" href="#konten">Lewati ke konten utama</a>

  <div class="toasts" id="toasts" aria-live="polite" aria-atomic="true">
    <?php foreach ($flashes as $flash): ?>
      <div class="toast" data-autohide="1">
        <?= icon($flash['tipe'] === 'error' ? 'close' : 'check') ?>
        <?= e($flash['pesan']) ?>
      </div>
    <?php endforeach; ?>
  </div>

  <header class="navbar">
    <div class="container nav-inner">
      <?= logo(url('index.php')) ?>
      <nav aria-label="Navigasi utama">
        <a class="nav-link<?= $activePage === 'katalog' ? ' active' : '' ?>"
           href="<?= e(url('index.php')) ?>">Katalog</a>
        <a class="nav-link<?= $activePage === 'bandingkan' ? ' active' : '' ?>"
           href="<?= e(url('bandingkan.php')) ?>">
          <?= icon('compare', 17) ?>
          Bandingkan <span class="count" id="compare-count">0</span>
        </a>
      </nav>
      <a class="admin-link" href="<?= e(url('admin/index.php')) ?>">
        <?= icon('shield', 16) ?>
        Admin
        <?= icon('arrow', 14) ?>
      </a>
    </div>
  </header>

  <div id="konten">
