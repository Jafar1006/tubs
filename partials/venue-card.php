<?php
/**
 * Komponen kartu gedung (dipakai halaman katalog & endpoint api/katalog.php
 * supaya markup hanya ditulis satu kali).
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.php';

function venue_card(array $v): string
{
    $detail = url('detail.php?id=' . $v['id']);
    $foto   = $v['photos'][0] ?? '';
    $badge  = $v['kategori'];

    ob_start(); ?>
    <article class="venue-card">
      <a class="card-photo" href="<?= e($detail) ?>" aria-label="Lihat detail <?= e($v['nama']) ?>">
        <img src="<?= e(foto_url($foto)) ?>" alt="<?= e($v['nama']) ?>" loading="lazy" width="400" height="158">
        <span class="category-badge"><?= e($badge) ?></span>
        <span class="photo-count"><?= icon('grid', 12) ?>4 foto</span>
      </a>
      <div class="card-body">
        <h3><a href="<?= e($detail) ?>"><?= e($v['nama']) ?></a></h3>
        <p class="card-location">
          <?= icon('pin', 14) ?>
          <?= e($v['kecamatan']) ?>, Bandung
        </p>
        <div class="capacity">
          <?= icon('people', 17) ?>
          <strong><?= e(ribuan($v['kapasitas'])) ?></strong> orang
          <span>Minimal <?= e($v['minimal_jam']) ?> jam</span>
        </div>
        <div class="card-price">
          <small>Harga sewa mulai dari</small>
          <div>
            <strong><?= e(rupiah($v['harga_per_jam'])) ?></strong>
            <span>/jam</span>
          </div>
        </div>
        <div class="card-actions">
          <a class="btn outline" href="<?= e($detail) ?>">
            Lihat Detail
            <?= icon('arrow', 14) ?>
          </a>
          <button type="button" class="btn compare-btn" data-compare-id="<?= (int) $v['id'] ?>">
            + Bandingkan
          </button>
        </div>
      </div>
    </article>
    <?php return (string) ob_get_clean();
}
