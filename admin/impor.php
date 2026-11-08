<?php
/**
 * Impor CSV: unggah file, pratinjau + validasi, lalu simpan data valid ke database.
 * Baris yang tidak valid ditampilkan merah beserta alasannya dan dilewati.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/icons.php';

require_login();

$step  = 'upload';   // upload | preview
$rows  = [];
$namaFile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'preview') {
        $file = $_FILES['csv'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('Pilih file CSV terlebih dahulu.', 'error');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            flash('Ukuran file maksimal 2 MB.', 'error');
        } else {
            $isi = (string) file_get_contents($file['tmp_name']);
            $rows = parse_csv($isi);
            if (!$rows) {
                flash('File CSV kosong atau header tidak sesuai template.', 'error');
            } else {
                $_SESSION['import_rows']  = $rows;
                $_SESSION['import_file']  = $file['name'];
                $step = 'preview';
                $namaFile = $file['name'];
            }
        }
    } elseif ($action === 'impor') {
        $rows = $_SESSION['import_rows'] ?? [];
        $namaFile = $_SESSION['import_file'] ?? '';
        if (!$rows) {
            flash('Tidak ada data untuk diimpor. Unggah file kembali.', 'error');
        } else {
            $berhasil = 0;
            foreach ($rows as $row) {
                if (!$row['errors']) {
                    import_csv_row($row['data']);
                    $berhasil++;
                }
            }
            unset($_SESSION['import_rows'], $_SESSION['import_file']);
            if ($berhasil > 0) {
                flash($berhasil . ' gedung berhasil diimpor');
                redirect('admin/gedung.php');
            }
            flash('Tidak ada baris valid untuk diimpor.', 'error');
        }
    } elseif ($action === 'batal') {
        unset($_SESSION['import_rows'], $_SESSION['import_file']);
        redirect('admin/impor.php');
    }
}

if ($step === 'upload' && isset($_SESSION['import_rows'])) {
    $rows     = $_SESSION['import_rows'];
    $namaFile = $_SESSION['import_file'] ?? '';
    $step     = 'preview';
}

$valid   = array_filter($rows, static fn (array $r): bool => !$r['errors']);
$invalid = array_filter($rows, static fn (array $r): bool => (bool) $r['errors']);

$adminSection = 'impor';
$pageTitle    = 'Impor CSV';
include __DIR__ . '/../partials/admin-header.php';
?>

<div class="page-heading">
  <div>
    <h1>Impor CSV</h1>
    <p>Tambahkan beberapa gedung sekaligus dengan file CSV.</p>
  </div>
  <a class="btn outline" href="<?= e(url('admin/template-csv.php')) ?>" download>
    Unduh template CSV
    <?= icon('upload', 16) ?>
  </a>
</div>

<section class="admin-panel panel-padding">
  <?php if ($step === 'upload'): ?>
    <form method="post" action="<?= e(url('admin/impor.php')) ?>" enctype="multipart/form-data" id="csv-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="preview">
      <label class="upload-zone" id="upload-zone" for="csv-file">
        <?= icon('upload', 40) ?>
        <h2 id="upload-title">Seret dan lepas file CSV di sini</h2>
        <p>
          Atau pilih file dari komputer. Kolom: <code>nama, kategori, kecamatan, kapasitas,
          harga_per_jam, minimal_jam, kontak_nama, kontak_wa, website_resmi</code>.
        </p>
        <span class="btn outline">Pilih file</span>
        <input id="csv-file" type="file" name="csv" accept=".csv,text/csv" required hidden>
      </label>
      <div class="button-row end">
        <button class="btn green" type="submit">Pratinjau data</button>
      </div>
    </form>
  <?php else: ?>
    <h3 class="preview-title">Pratinjau data · <?= count($rows) ?> baris</h3>
    <p class="secondary small">File: <strong><?= e($namaFile) ?></strong></p>

    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nama gedung</th>
            <th>Kecamatan</th>
            <th>Kapasitas</th>
            <th>Harga/jam</th>
            <th>Nama kontak resmi</th>
            <th>Nomor WhatsApp resmi</th>
            <th>URL website resmi</th>
            <th>Validasi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): $d = $row['data']; ?>
            <tr class="<?= $row['errors'] ? 'invalid-row' : '' ?>">
              <td><?= e($d['nama'] ?? '') ?></td>
              <td><?= e($d['kecamatan'] ?? '') ?></td>
              <td><?= e($d['kapasitas'] ?? '') ?></td>
              <td class="nowrap"><?= e(rupiah((int) ($d['harga_per_jam'] ?? 0))) ?></td>
              <td><?= e($d['kontak_nama'] ?? '-') ?></td>
              <td><?= e($d['kontak_wa'] ?? '-') ?></td>
              <td><?= e($d['website_resmi'] ?? '-') ?></td>
              <td class="<?= $row['errors'] ? 'error' : 'yes' ?>">
                <?= $row['errors'] ? e(implode(' · ', $row['errors'])) : 'Valid' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="import-actions">
      <p>
        <?= count($valid) ?> data valid
        <?php if ($invalid): ?>
          · <?= count($invalid) ?> data tidak valid akan dilewati.
        <?php endif; ?>
      </p>
      <div class="button-row">
        <form method="post" action="<?= e(url('admin/impor.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="batal">
          <button class="btn outline" type="submit">Batal</button>
        </form>
        <form method="post" action="<?= e(url('admin/impor.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="impor">
          <button class="btn green" type="submit" <?= count($valid) === 0 ? 'disabled' : '' ?>>
            Impor <?= count($valid) ?> data valid
          </button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
