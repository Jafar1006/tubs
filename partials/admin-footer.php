<?php
/**
 * Penutup layout admin + pemuatan skrip.
 */

declare(strict_types=1);
?>
      </main>
    </div>
  </div>

  <script>
    window.GI = {
      base: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
      admin: true
    };
  </script>
  <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
