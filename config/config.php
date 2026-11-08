<?php
/**
 * Gedung Inn — konfigurasi aplikasi.
 *
 * Nilai DB_* bisa dioverride lewat environment variable, misalnya:
 *   GI_DB_PORT=3307 php -S localhost:8000
 */

declare(strict_types=1);

// Tampilkan error saat pengembangan. Set ke false untuk produksi.
ini_set('display_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'Gedung Inn');
define('APP_TAGLINE', 'Ruang yang tepat. Momen yang berkesan.');
define('APP_YEAR', '2026');

define('DB_HOST', getenv('GI_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('GI_DB_PORT') ?: '3306');
define('DB_NAME', getenv('GI_DB_NAME') ?: 'gedung_inn');
define('DB_USER', getenv('GI_DB_USER') ?: 'root');
define('DB_PASS', getenv('GI_DB_PASS') !== false ? (string) getenv('GI_DB_PASS') : '');

/**
 * BASE_URL dideteksi otomatis supaya project tetap jalan baik di
 * http://localhost/gedung-inn/ maupun di root domain / PHP built-in server.
 */
if (!defined('BASE_URL')) {
    $base = '';
    if (PHP_SAPI !== 'cli' && isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME'])) {
        $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']));
        $appRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
        if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}

/** Session hanya untuk request web, bukan CLI. */
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // Simpan session di dalam project agar tidak bergantung izin folder temp sistem.
    $sessionDir = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0775, true);
    }
    if (is_dir($sessionDir)) {
        session_save_path($sessionDir);
    }

    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
