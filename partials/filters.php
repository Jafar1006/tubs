<?php
/**
 * Panel filter (sidebar desktop + drawer mobile).
 * Dirender dua kali dengan prefix berbeda agar id tetap unik.
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.php';

/**
 * @param array $f       hasil parse_filters()
 * @param array $masters hasil masters()
 * @param array $counts  ['kecamatan' => [nama => jumlah], 'kategori' => [nama => jumlah]]
 * @param string $prefix prefix id agar unik antara sidebar & drawer
 * @param array $hidden  input tersembunyi tambahan (q, sort)
 */
function render_filters(array $f, array $masters, array $counts, string $prefix = 'f', array $hidden = []): string
{
    $ceiling = price_ceiling();
    ob_start(); ?>
    <form id="filters-<?= e($prefix) ?>" data-filter-form
          method="get" action="<?= e(url('index.php')) ?>">
      <div class="filter-head">
        <h3><?= icon('filter', 18) ?> Filter gedung</h3>
        <a class="text-btn" href="<?= e(url('index.php')) ?>">Reset filter</a>
      </div>

      <section class="filter-section">
        <label class="filter-title" for="<?= e($prefix) ?>-harga-min">Harga per jam</label>
        <div class="range-track">
          <input id="<?= e($prefix) ?>-harga-min" type="range" name="min" aria-label="Harga minimum"
                 min="0" max="<?= (int) $ceiling ?>" step="50000" value="<?= (int) $f['min'] ?>"
                 data-range="min" data-format="rupiah">
          <input id="<?= e($prefix) ?>-harga-max" type="range" name="max" aria-label="Harga maksimum"
                 min="0" max="<?= (int) $ceiling ?>" step="50000" value="<?= (int) $f['max'] ?>"
                 data-range="max" data-format="rupiah" data-ceiling="<?= (int) $ceiling ?>">
        </div>
        <div class="price-range">
          <span>
            <small>Minimum</small>
            <b data-price-min><?= e(rupiah($f['min'])) ?></b>
          </span>
          <span class="range-dash">–</span>
          <span>
            <small>Maksimum</small>
            <b data-price-max><?= e(rupiah($f['max'])) ?></b>
          </span>
        </div>
      </section>

      <section class="filter-section">
        <label class="filter-title" for="<?= e($prefix) ?>-kapasitas">Kapasitas minimal</label>
        <div class="input-suffix">
          <input id="<?= e($prefix) ?>-kapasitas" type="number" name="kapasitas" min="0"
                 placeholder="Contoh: 100" value="<?= $f['kapasitas'] > 0 ? (int) $f['kapasitas'] : '' ?>">
          <span>orang</span>
        </div>
      </section>

      <section class="filter-section">
        <h4>Kecamatan</h4>
        <?php foreach (kecamatan_list() as $d): ?>
          <label class="checkbox-row">
            <input type="checkbox" name="kecamatan[]" value="<?= e($d) ?>"
              <?= in_array($d, $f['kecamatan'], true) ? 'checked' : '' ?>>
            <span><?= e($d) ?></span>
            <small><?= (int) ($counts['kecamatan'][$d] ?? 0) ?></small>
          </label>
        <?php endforeach; ?>
      </section>

      <section class="filter-section">
        <h4>Kategori</h4>
        <?php foreach ($masters['Kategori'] as $nama): ?>
          <label class="checkbox-row">
            <input type="checkbox" name="kategori[]" value="<?= e($nama) ?>"
              <?= in_array($nama, $f['kategori'], true) ? 'checked' : '' ?>>
            <span><?= e($nama) ?></span>
            <small><?= (int) ($counts['kategori'][$nama] ?? 0) ?></small>
          </label>
        <?php endforeach; ?>
      </section>

      <section class="filter-section">
        <h4>Jenis acara</h4>
        <div class="chips">
          <?php foreach ($masters['Jenis Acara'] as $nama):
              $aktif = in_array($nama, $f['acara'], true); ?>
            <button type="button" class="chip<?= $aktif ? ' chosen' : '' ?>"
                    data-chip aria-pressed="<?= $aktif ? 'true' : 'false' ?>"><?= e($nama) ?></button>
            <input type="hidden" name="acara[]" value="<?= e($nama) ?>" <?= $aktif ? '' : 'disabled' ?>
                   data-chip-input>
          <?php endforeach; ?>
        </div>
      </section>

      <div class="filter-note">
        <?= icon('shield', 18) ?>
        <span>Informasi transparan untuk keputusan yang lebih tenang.</span>
      </div>

      <input type="hidden" name="q" value="<?= e($hidden['q'] ?? $f['q']) ?>" data-q-input>
      <input type="hidden" name="sort" value="<?= e($hidden['sort'] ?? $f['sort']) ?>" data-sort-input>
      <noscript>
        <button class="btn green full" type="submit" style="margin-top:16px">Terapkan filter</button>
      </noscript>
    </form>
    <?php return (string) ob_get_clean();
}
