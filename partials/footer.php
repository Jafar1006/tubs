<?php
/**
 * Footer halaman publik + wadah compare tray + pemuatan JavaScript.
 */

declare(strict_types=1);
?>
  </div><!-- /#konten -->

  <footer class="footer container">
    <?= logo(url('index.php')) ?>
    <p><?= e(APP_TAGLINE) ?></p>
    <span>© <?= e(APP_YEAR) ?> <?= e(APP_NAME) ?> · Dibuat untuk Bandung</span>
  </footer>

  <!-- Compare tray: dirender oleh assets/js/app.js dari localStorage -->
  <div id="compare-tray"></div>

  <script>
    window.GI = {
      base: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
      katalogApi: <?= json_encode(url('api/katalog.php'), JSON_UNESCAPED_SLASHES) ?>,
      trayApi: <?= json_encode(url('api/tray.php'), JSON_UNESCAPED_SLASHES) ?>,
      bandingkan: <?= json_encode(url('bandingkan.php'), JSON_UNESCAPED_SLASHES) ?>,
      detail: <?= json_encode(url('detail.php'), JSON_UNESCAPED_SLASHES) ?>,
      maxCompare: 3
    };
  </script>
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
