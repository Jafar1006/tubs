<?php
/**
 * Unduh template CSV (header + 1 baris contoh).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repository.php';

require_login();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template-gedung.csv"');
header('Cache-Control: no-store');

echo csv_template_content();
