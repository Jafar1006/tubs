<?php
/**
 * Halaman Detail Gedung: galeri foto, informasi lengkap, panel estimasi biaya,
 * dan aksi kontak resmi (satu-satunya tempat tombol WhatsApp & website).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/repository.php';
require_once __DIR__ . '/partials/icons.php';
require_once __DIR__ . '/partials/contact.php';

$id    = (int) ($_GET['id'] ?? 0);
$venue = $id > 0 ? get_venue($id) : null;

if ($venue === null) {
    http_response_code(404);
    $pageTitle  = 'Gedung tidak ditemukan';
    $activePage = 'katalog';
    include __DIR__ . '/partials/header.php';
    ?>
    <main class="container">
      <div class="empty">
        <span class="empty-icon"><?= icon('building', 40) ?></span>
        <h2>Gedung tidak ditemukan</h2>
        <p>Data gedung yang Anda cari tidak tersedia atau sudah dihapus.</p>
        <a class="btn green" href="<?= e(url('index.php')) ?>">
          Ke katalog
          <?= icon('arrow', 16) ?>
        </a>
      </div>
    </main>
    <?php
    include __DIR__ . '/partials/footer.php';
    exit;
}

$photos = $venue['photos'];
$photos = array_values(array_filter($photos, static fn (string $p): bool => $p !== ''));
if (!$photos) {
    $photos = ['venue-1.jpg'];
}

$totalAwal = $venue['harga_per_jam'] * $venue['minimal_jam'];
$venueJson = [
    'id'      => $venue['id'],
    'harga'   => $venue['harga_per_jam'],
    'minimal' => $venue['minimal_jam'],
    'layanan' => $venue['layanan'],
];

$pageTitle  = $venue['nama'];
$activePage = 'detail';
include __DIR__ . '/partials/header.php';
?>

<main class="container detail-page">
  <a class="back-btn" id="back-btn" href="<?= e(url('index.php')) ?>">
    <?= icon('arrowLeft', 17) ?>
    Kembali
  </a>

  <div class="gallery">
    <img class="gallery-main" id="gallery-main" src="<?= e(foto_url($photos[0])) ?>"
         alt="<?= e($venue['nama']) ?>, foto 1" width="900" height="380">
    <div class="gallery-thumbs" id="gallery-thumbs">
      <?php foreach (array_slice($photos, 1) as $i => $p): ?>
        <button type="button" data-photo="<?= e(foto_url($p)) ?>"
                aria-label="Tampilkan foto <?= $i + 2 ?>">
          <img src="<?= e(foto_url($p)) ?>" alt="Sudut gedung <?= $i + 2 ?>" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="detail-columns">
    <section>
      <span class="tag"><?= e($venue['kategori']) ?></span>
      <h1><?= e($venue['nama']) ?></h1>
      <p class="inline-icon secondary">
        <?= icon('pin', 18) ?>
        <?= e($venue['alamat']) ?>
      </p>
      <div class="detail-numbers">
        <span>
          <?= icon('people') ?>
          <strong><?= e(ribuan($venue['kapasitas'])) ?></strong> orang
        </span>
        <span><strong><?= e(rupiah($venue['harga_per_jam'])) ?></strong>/jam</span>
        <small>Minimal sewa <?= (int) $venue['minimal_jam'] ?> jam</small>
      </div>

      <section class="detail-section">
        <h2>Tentang gedung</h2>
        <p><?= nl2br(e($venue['deskripsi'])) ?></p>
      </section>

      <section class="detail-section">
        <h2>Cocok untuk acara</h2>
        <div class="chips">
          <?php foreach ($venue['jenis_acara'] as $jenis): ?>
            <span class="chip"><?= e($jenis) ?></span>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="detail-section">
        <h2>Fasilitas gratis</h2>
        <div class="facility-grid">
          <?php foreach ($venue['fasilitas'] as $fasilitas): ?>
            <span><?= icon('check', 17) ?><?= e($fasilitas) ?></span>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="detail-section">
        <h2>Layanan tambahan</h2>
        <?php foreach ($venue['layanan'] as $layanan): ?>
          <div class="line-item">
            <span><?= e($layanan['nama']) ?></span>
            <strong>
              <?= e(rupiah($layanan['harga'])) ?>
              <small>/<?= $layanan['satuan'] === 'per_jam' ? 'jam' : 'sekali' ?></small>
            </strong>
          </div>
        <?php endforeach; ?>
        <?php if (!$venue['layanan']): ?>
          <p class="secondary small">Belum ada layanan tambahan.</p>
        <?php endif; ?>
      </section>

      <?= contact_section($venue) ?>

      <div class="button-row">
        <button type="button" class="btn compare-btn full" data-compare-id="<?= (int) $venue['id'] ?>">
          + Bandingkan
        </button>
      </div>
    </section>

    <aside class="estimate-panel">
      <div class="panel-title">
        <?= icon('building') ?>
        <h2>Estimasi Biaya</h2>
      </div>
      <p>Rencanakan acara sesuai anggaran Anda.</p>

      <label class="filter-title" for="duration">Durasi sewa (jam)</label>
      <div class="stepper">
        <button type="button" id="duration-minus" aria-label="Kurangi durasi">−</button>
        <input id="duration" type="number" min="1" value="<?= (int) $venue['minimal_jam'] ?>"
               aria-label="Durasi sewa">
        <button type="button" id="duration-plus" aria-label="Tambah durasi">+</button>
      </div>
      <p class="error" id="duration-warning" <?= $venue['minimal_jam'] > 0 ? 'hidden' : '' ?>>
        Minimal sewa <?= (int) $venue['minimal_jam'] ?> jam
      </p>

      <h4>Layanan tambahan <small>(opsional)</small></h4>
      <?php foreach ($venue['layanan'] as $i => $layanan): ?>
        <label class="checkbox-row service-check">
          <input type="checkbox" data-service="<?= $i ?>"
                 data-harga="<?= (int) $layanan['harga'] ?>"
                 data-satuan="<?= e($layanan['satuan']) ?>">
          <span>
            <?= e($layanan['nama']) ?>
            <small><?= e(rupiah($layanan['harga'])) ?>/<?= $layanan['satuan'] === 'per_jam' ? 'jam' : 'sekali' ?></small>
          </span>
        </label>
      <?php endforeach; ?>

      <div class="estimate-lines" id="estimate-lines">
        <div class="line-item">
          <span>Sewa gedung × <?= (int) $venue['minimal_jam'] ?> jam</span>
          <strong><?= e(rupiah($totalAwal)) ?></strong>
        </div>
        <div class="total">
          <span>Total estimasi</span>
          <strong id="estimate-total"><?= e(rupiah($totalAwal)) ?></strong>
        </div>
      </div>

      <?= contact_actions($venue) ?>

      <small class="estimate-note">
        <?= icon('shield', 14) ?>
        Estimasi biaya ini merupakan perkiraan berdasarkan informasi yang tersedia. Untuk
        mendapatkan informasi harga terbaru dan ketentuan penyewaan, silakan hubungi pengelola
        gedung secara langsung.
      </small>
      <?= contact_notice($venue) ?>
    </aside>
  </div>
</main>

<script type="application/json" id="venue-data"><?= json_encode($venueJson, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php include __DIR__ . '/partials/footer.php'; ?>
