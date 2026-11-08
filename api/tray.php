<?php
/**
 * Endpoint compare tray: data ringkas gedung yang dipilih untuk dibandingkan.
 * Daftar id dikirim JavaScript dari localStorage.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';

header('Content-Type: application/json; charset=utf-8');

$ids = [];
if (isset($_GET['ids'])) {
    foreach (explode(',', (string) $_GET['ids']) as $raw) {
        $id = (int) trim($raw);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
}
$ids = array_slice(array_values(array_unique($ids)), 0, 3);

$venues = get_venues_by_ids($ids);
$slots  = [];
foreach ($venues as $v) {
    $slots[] = [
        'id'   => $v['id'],
        'nama' => $v['nama'],
        'foto' => foto_url($v['photos'][0] ?? ''),
        'url'  => url('detail.php?id=' . $v['id']),
    ];
}

echo json_encode([
    'ok'    => true,
    'slots' => $slots,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
