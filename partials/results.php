<?php
/**
 * Daftar hasil katalog: grid kartu atau empty state.
 * Dipakai index.php (render awal) dan api/katalog.php (pemfilteran via AJAX).
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/venue-card.php';

function render_results(array $venues): string
{
    if (!$venues) {
        ob_start(); ?>
        <div class="empty">
          <span class="empty-icon"><?= icon('building', 40) ?></span>
          <h2>Tidak ada gedung yang cocok</h2>
          <p>Coba ubah kata kunci atau filter pencarian Anda.</p>
          <a class="btn green" href="<?= e(url('index.php')) ?>">
            Reset filter
            <?= icon('arrow', 16) ?>
          </a>
        </div>
        <?php return (string) ob_get_clean();
    }

    ob_start(); ?>
    <div class="venue-grid" id="venue-grid">
      <?php foreach ($venues as $v) {
          echo venue_card($v);
      } ?>
    </div>
    <?php return (string) ob_get_clean();
}
