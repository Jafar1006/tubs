<?php
/**
 * Helper umum: escaping, format Rupiah, URL, CSRF, flash message, dan autentikasi.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/* ---------------------------------------------------------------------
   Escaping & format
   --------------------------------------------------------------------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Rp 1.500.000 */
function rupiah(int|float $angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/** 1.500 */
function ribuan(int|float $angka): string
{
    return number_format((float) $angka, 0, ',', '.');
}

/* ---------------------------------------------------------------------
   URL & aset
   --------------------------------------------------------------------- */

/** URL relatif terhadap root aplikasi: url('detail.php?id=1') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Aset statis: asset('css/style.css') */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Foto gedung bisa berupa nama file contoh (venue-1.jpg) atau URL eksternal.
 */
function foto_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return asset('img/venue-1.jpg');
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }
    if (str_starts_with($value, 'assets/')) {
        return url($value);
    }

    return asset('img/' . ltrim($value, '/'));
}

/* ---------------------------------------------------------------------
   Format pesan WhatsApp & website resmi
   --------------------------------------------------------------------- */

/** Nomor WA: 08xx -> 628xx, hanya digit. */
function wa_number(string $nomor): ?string
{
    $nomor = preg_replace('/\D+/', '', $nomor) ?? '';
    $nomor = preg_replace('/^0/', '62', $nomor) ?? '';
    if (!preg_match('/^\d{10,15}$/', $nomor)) {
        return null;
    }

    return $nomor;
}

function wa_url(string $nama, string $kontakWa): ?string
{
    $nomor = wa_number($kontakWa);
    if ($nomor === null) {
        return null;
    }
    $pesan = 'Halo, saya mendapatkan informasi mengenai ' . $nama
        . ' melalui Gedung Inn. Saya ingin mengetahui informasi lebih lanjut mengenai harga, '
        . 'ketersediaan, dan ketentuan penyewaan gedung.';

    return 'https://wa.me/' . $nomor . '?text=' . rawurlencode($pesan);
}

/** Validasi URL website dengan aturan yang sama seperti prototipe. */
function website_url(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $parts = parse_url($value);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        return null;
    }
    if (!str_contains($parts['host'], '.')) {
        return null;
    }
    if (isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }

    return $value;
}

/* ---------------------------------------------------------------------
   Daftar kecamatan (data statis, sesuai prototipe)
   --------------------------------------------------------------------- */

function kecamatan_list(): array
{
    return ['Coblong', 'Sukajadi', 'Bandung Wetan', 'Lengkong', 'Buahbatu', 'Cidadap'];
}

/* ---------------------------------------------------------------------
   CSRF
   --------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Silakan muat ulang halaman.');
    }
}

/* ---------------------------------------------------------------------
   Flash message (toast setelah redirect)
   --------------------------------------------------------------------- */

function flash(string $pesan, string $tipe = 'sukses'): void
{
    $_SESSION['flash'][] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function flash_set(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $items;
}

/* ---------------------------------------------------------------------
   Redirect
   --------------------------------------------------------------------- */

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ---------------------------------------------------------------------
   Autentikasi admin
   --------------------------------------------------------------------- */

function current_admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function is_logged_in(): bool
{
    return current_admin() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('Silakan masuk terlebih dahulu.', 'error');
        redirect('admin/login.php');
    }
}
