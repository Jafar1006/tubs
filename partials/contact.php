<?php
/**
 * Blok informasi kontak & aksi resmi (WhatsApp + website).
 * Sama seperti prototipe: aksi hanya muncul di panel estimasi,
 * sedangkan data kontak tampil sebagai teks di kolom konten.
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.php';

function contact_notice(array $v): string
{
    $pesan = !empty($v['kontak_demo'])
        ? 'Data contoh prototipe: nomor WhatsApp hanya untuk simulasi dan website mengarah ke example.com, bukan kontak resmi terverifikasi gedung ini.'
        : 'Informasi kontak disediakan oleh pengelola data dan belum diverifikasi oleh Gedung Inn.';

    return '<p class="secondary small contact-notice">' . e($pesan) . '</p>';
}

/** Dua tombol utama: WhatsApp pengelola + website resmi. */
function contact_actions(array $v): string
{
    $wa      = wa_url((string) $v['nama'], (string) $v['kontak_wa']);
    $website = website_url((string) $v['website_resmi']);

    $html  = '<div class="contact-actions">';
    $html .= $wa
        ? '<a class="btn green full" href="' . e($wa) . '" target="_blank" rel="noopener noreferrer">'
            . 'Hubungi Pengelola' . icon('arrow', 17) . '</a>'
        : '<p class="secondary small">Kontak WhatsApp belum tersedia.</p>';
    $html .= $website
        ? '<a class="btn outline full" href="' . e($website) . '" target="_blank" rel="noopener noreferrer">'
            . 'Website Resmi' . icon('arrow', 17) . '</a>'
        : '<p class="secondary small">Website resmi belum tersedia.</p>';
    $html .= '</div>';

    return $html;
}

/** Bagian "Informasi Kontak & Website Resmi" pada halaman detail. */
function contact_section(array $v): string
{
    ob_start(); ?>
    <section class="detail-section contact-section">
      <h2>Informasi Kontak &amp; Website Resmi</h2>
      <?= contact_notice($v) ?>
      <dl class="contact-details">
        <div>
          <dt>Nama pengelola atau kontak resmi</dt>
          <dd><?= e($v['kontak_nama'] !== '' ? $v['kontak_nama'] : 'Belum tersedia') ?></dd>
        </div>
        <div>
          <dt>Nomor WhatsApp resmi</dt>
          <dd><?= e($v['kontak_wa'] !== '' ? $v['kontak_wa'] : 'Belum tersedia') ?></dd>
        </div>
        <div>
          <dt>URL website resmi</dt>
          <dd><?= e($v['website_resmi'] !== '' ? $v['website_resmi'] : 'Belum tersedia') ?></dd>
        </div>
      </dl>
      <p class="secondary small contact-notice">
        Komunikasi, negosiasi, pengecekan ketersediaan, dan proses penyewaan dilakukan langsung
        dengan pengelola di luar Gedung Inn.
      </p>
    </section>
    <?php return (string) ob_get_clean();
}
