<?php
/**
 * Koneksi database (PDO + MySQL).
 * Satu koneksi dipakai ulang selama satu request (pola singleton sederhana).
 *
 * Port dicoba berurutan: DB_PORT dari config, lalu 3306, lalu 3307.
 * Ini membuat aplikasi tetap jalan baik pada XAMPP standar (3306) maupun
 * saat port 3306 sudah dipakai instance MySQL lain (XAMPP digeser ke 3307).
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $ports = array_values(array_unique(array_filter([
        (string) DB_PORT,
        '3306',
        '3307',
    ], static fn (string $p): bool => $p !== '')));

    $lastError = null;

    foreach ($ports as $port) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, $port, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 4,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    http_response_code(500);
    $pesan = htmlspecialchars($lastError ? $lastError->getMessage() : 'tidak diketahui', ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<title>Koneksi database gagal</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#F7F6F2;color:#1A1A1A;'
        . 'display:grid;place-items:center;min-height:100vh;margin:0;padding:24px}'
        . 'div{background:#fff;border:1px solid #E3E1DA;border-radius:12px;padding:32px;max-width:640px}'
        . 'h1{color:#1F4D3A;margin-top:0}code{background:#f4f2ec;padding:2px 6px;border-radius:4px}'
        . 'li{margin-bottom:6px}</style></head><body><div>'
        . '<h1>Koneksi database gagal</h1>'
        . '<p>Pastikan MySQL sudah berjalan dan database <code>' . DB_NAME . '</code> sudah diimpor.</p>'
        . '<ol>'
        . '<li>Jalankan MySQL (XAMPP: tombol Start pada MySQL).</li>'
        . '<li>Impor <code>database/schema.sql</code> lewat phpMyAdmin.</li>'
        . '<li>Sesuaikan host/user/password di <code>config/config.php</code> bila perlu.</li>'
        . '</ol>'
        . '<p style="color:#6B6B6B;font-size:13px">Port dicoba: ' . implode(', ', $ports)
        . ' · Detail: ' . $pesan . '</p>'
        . '</div></body></html>';
    exit;
}
