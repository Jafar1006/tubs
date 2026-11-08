<?php
/**
 * Endpoint katalog: mengembalikan HTML kartu + jumlah hasil.
 * Dipanggil assets/js/app.js saat filter berubah (tanpa reload halaman).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../partials/results.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $filters = parse_filters($_GET);
    $venues  = find_venues($filters);

    echo json_encode([
        'ok'     => true,
        'count'  => count($venues),
        'active' => active_filter_count($filters),
        'html'   => render_results($venues),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
