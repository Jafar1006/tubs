<?php
/**
 * Halaman Katalog — pencarian, filter, dan daftar gedung.
 *
 * Filter bekerja dua lapis:
 *   1. Server-side (PHP + MySQL) lewat query string -> tetap jalan tanpa JavaScript.
 *   2. Client-side (JavaScript) memanggil api/katalog.php agar terasa instan.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/partials/icons.php';
require_once __DIR__ . '/partials/filters.php';
require_once __DIR__ . '/partials/results.php';

$filters = parse_filters($_GET);
$venues  = find_venues($filters);
$masters = masters();
$counts  = filter_counts();

$active    = active_filter_count($filters);
$stats     = venue_stats();
$heroVenue = get_venue(1);

$pageTitle  = 'Katalog';
$activePage = 'katalog';

include __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <div class="eyebrow"><span></span> RUANG UNTUK SETIAP CERITA</div>
      <h1>
        Temukan gedung acara<br>
        di <span>Bandung.</span>
      </h1>
      <p>
        Dari pertemuan kecil hingga perayaan besar. Temukan, bandingkan,<br class="desktop">
        dan pilih gedung yang pas untuk acara dan anggaran Anda.
      </p>
      <form class="search-form" id="hero-search" role="search" method="get" action="<?= e(url('index.php')) ?>">
        <?= icon('search') ?>
        <input type="search" name="q" aria-label="Cari nama gedung" autocomplete="off"
               placeholder="Cari nama gedung yang kamu inginkan..." value="<?= e($filters['q']) ?>">
        <button class="btn green" type="submit">
          Cari gedung
          <?= icon('arrow', 17) ?>
        </button>
      </form>
      <div class="hero-benefits">
        <span><?= icon('check', 15) ?> Harga transparan</span>
        <span><?= icon('check', 15) ?> Bandingkan hingga 3 gedung</span>
        <span><?= icon('check', 15) ?> Tanpa biaya pencarian</span>
      </div>
    </div>
    <div class="hero-art">
      <div class="hero-image">
        <img src="<?= e(foto_url($heroVenue['photos'][0] ?? 'venue-1.jpg')) ?>"
             alt="Ruang acara yang elegan dengan dekorasi pernikahan">
        <div class="image-shade"></div>
        <div class="hero-photo-caption">
          <span>SETIAP MOMEN PUNYA TEMPATNYA</span>
          <strong>Mulai cerita Anda di sini.</strong>
        </div>
      </div>
      <div class="floating-location">
        <span><?= icon('pin', 20) ?></span>
        <div>
          <strong>Bandung, Jawa Barat</strong>
          <small>Pilihan ruang, sejuta kemungkinan</small>
        </div>
      </div>
      <div class="art-dots"></div>
    </div>
  </div>
</section>

<div class="trust-strip">
  <div class="container">
    <span><?= icon('building') ?> <strong><?= (int) $stats['total'] ?> pilihan gedung</strong> di satu tempat</span>
    <span><?= icon('compare') ?> <strong>Lebih mudah</strong> dibandingkan</span>
    <span><?= icon('clock') ?> <strong>Estimasi biaya</strong> secara langsung</span>
  </div>
</div>

<main class="container catalog" id="catalog">
  <div class="catalog-heading">
    <div class="eyebrow muted">PILIHAN RUANG DI KOTA KEMBANG</div>
    <h2>Gedung pilihan untuk acara Anda</h2>
    <p>Temukan ruang yang sesuai kebutuhan, bukan sekadar tempat.</p>
  </div>

  <div class="catalog-layout">
    <aside class="filters">
      <?= render_filters($filters, $masters, $counts, 'f') ?>
    </aside>

    <section class="results">
      <div class="results-bar">
        <div>
          <strong id="result-count"><?= count($venues) ?> gedung</strong>
          <span> ditemukan</span>
          <span class="active-filter-count" id="active-filter-count"
                <?= $active === 0 ? 'hidden' : '' ?>><?= $active ?> filter</span>
        </div>
        <button class="btn outline mobile-filter" id="open-filter" type="button">
          <?= icon('filter', 16) ?>
          Filter<span id="mobile-filter-count"><?= $active ? ' (' . $active . ')' : '' ?></span>
        </button>
        <label class="sort-label">
          Urutkan:
          <select aria-label="Urutkan gedung" id="sort-select" name="sort">
            <option value="rekomendasi" <?= $filters['sort'] === 'rekomendasi' ? 'selected' : '' ?>>Rekomendasi</option>
            <option value="rendah"      <?= $filters['sort'] === 'rendah' ? 'selected' : '' ?>>Harga terendah</option>
            <option value="tinggi"      <?= $filters['sort'] === 'tinggi' ? 'selected' : '' ?>>Harga tertinggi</option>
            <option value="kapasitas"   <?= $filters['sort'] === 'kapasitas' ? 'selected' : '' ?>>Kapasitas terbesar</option>
          </select>
        </label>
      </div>

      <div id="venue-results">
        <?= render_results($venues) ?>
      </div>

      <div class="results-end">
        <span></span>
        Anda telah melihat semua <span id="result-count-end"><?= count($venues) ?></span> gedung
        <span></span>
      </div>

      <div class="catalog-tip">
        <span class="tip-icon"><?= icon('compare', 25) ?></span>
        <div>
          <h3>Masih bingung memilih?</h3>
          <p>Pilih hingga 3 gedung dan bandingkan harga, kapasitas, serta fasilitasnya sekaligus.</p>
        </div>
      </div>
    </section>
  </div>
</main>

<!-- Drawer filter untuk layar kecil -->
<div class="overlay" id="filter-drawer" hidden>
  <div class="modal drawer" role="dialog" aria-modal="true" aria-label="Filter gedung">
    <header class="modal-head">
      <h2>Filter gedung</h2>
      <button class="icon-btn" type="button" data-close-drawer aria-label="Tutup">
        <?= icon('close') ?>
      </button>
    </header>
    <div class="drawer-body">
      <?= render_filters($filters, $masters, $counts, 'd') ?>
      <button class="btn green full" type="button" data-apply-filters id="apply-filters">
        Tampilkan <?= count($venues) ?> gedung
      </button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
