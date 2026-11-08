<?php
/**
 * Ikon SVG — dipindahkan apa adanya dari prototipe (src/ui.tsx)
 * supaya tampilan tetap identik tanpa library tambahan.
 */

declare(strict_types=1);

function icon(string $name, int $size = 20): string
{
    static $paths = [
        'building' => '<path d="M4 21V7l8-4 8 4v14M2 21h20M9 21v-5h6v5M8 9h1m6 0h1M8 12h1m6 0h1"/>',
        'search'   => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'pin'      => '<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'people'   => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m3 11v-3a6 6 0 0 0-3-5"/>',
        'compare'  => '<path d="M5 5v14M12 3v18M19 7v10M2 8h6m1 8h6m1-6h6"/>',
        'arrow'    => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'arrowLeft' => '<path d="M20 12H4m6-6-6 6 6 6"/>',
        'check'    => '<path d="m5 12 4 4L19 6"/>',
        'close'    => '<path d="m6 6 12 12M6 18 18 6"/>',
        'filter'   => '<path d="M4 7h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="16" cy="17" r="2"/>',
        'shield'   => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'upload'   => '<path d="M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6"/>',
        'logout'   => '<path d="M9 4H4v16h5m5-13 5 5-5 5m-6-5h14"/>',
        'leaf'     => '<path d="M20 4C8 2 2 8 6 16s16 1 14-12ZM5 20l10-11"/>',
    ];

    $inner = $paths[$name] ?? $paths['building'];

    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none"'
        . ' stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true">' . $inner . '</svg>';
}

/** Logo "gedunginn." — bisa berupa tautan ke halaman mana pun. */
function logo(string $href): string
{
    return '<a class="logo" href="' . e($href) . '" aria-label="Gedung Inn — beranda">'
        . '<span class="logo-mark">' . icon('building', 25) . '</span>'
        . '<span>gedung<span class="logo-light">inn</span><span class="logo-dot">.</span></span>'
        . '</a>';
}
