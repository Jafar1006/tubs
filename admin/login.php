<?php
/**
 * Halaman login admin.
 * Akun demo: admin@gedunginn.id / admin123 (lihat database/schema.sql)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

if (is_logged_in()) {
    redirect('admin/index.php');
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id'    => (int) $user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
            ];
            flash('Selamat datang, ' . $user['nama'] . '.');
            redirect('admin/index.php');
        }

        $error = 'Email atau password salah';
    }
}

$flashes = flash_set();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Masuk Admin · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
  <div class="toasts" id="toasts" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
      <div class="toast" data-autohide="1">
        <?= icon($flash['tipe'] === 'error' ? 'close' : 'check') ?>
        <?= e($flash['pesan']) ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="login-page">
    <div class="login-brand">
      <?= logo(url('index.php')) ?>
      <h1>Ruang untuk cerita.<br>Kemudahan untuk Anda.</h1>
      <p>Kelola informasi gedung dan kontak pengelola dalam satu tempat.</p>
      <a class="btn outline" href="<?= e(url('index.php')) ?>">
        Kembali ke katalog
        <?= icon('arrow', 17) ?>
      </a>
    </div>

    <form class="login-card" method="post" action="<?= e(url('admin/login.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <span class="empty-icon"><?= icon('shield', 30) ?></span>
      <h2>Selamat datang kembali</h2>
      <p>Masuk ke area admin <?= e(APP_NAME) ?>.</p>

      <label class="field" for="email">
        Email
        <input id="email" name="email" type="email" required autocomplete="username"
               value="<?= e($email) ?>" placeholder="admin@gedunginn.id"
               <?= $error !== '' ? 'aria-invalid="true"' : '' ?>>
      </label>

      <label class="field" for="password">
        Kata sandi
        <input id="password" name="password" type="password" required autocomplete="current-password"
               placeholder="Masukkan kata sandi"
               <?= $error !== '' ? 'aria-invalid="true"' : '' ?>>
      </label>

      <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= e($error) ?></p>
      <?php endif; ?>

      <button class="btn green full" type="submit">
        Masuk
        <?= icon('arrow', 17) ?>
      </button>

      <div class="demo-credentials">
        <strong>Akun demo</strong>
        <p>admin@gedunginn.id / admin123</p>
        <small>Data tersimpan di database MySQL</small>
      </div>
    </form>
  </div>

  <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
