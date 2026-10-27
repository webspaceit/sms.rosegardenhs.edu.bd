<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Inline SVG icons.
 *
 * The project ships without a bundler, so icon fonts and sprite files are not
 * an option. Every glyph is stored here as raw SVG markup and rendered on the
 * server. Icons inherit `currentColor`, which lets the navigation restyle them
 * per state (muted, active, disabled) using plain Tailwind text colours.
 */
final class Icon
{
    /** @var array<string, string> */
    private const PATHS = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/>',
        'cap' => '<path d="M3 9l9-5 9 5-9 5-9-5z"/><path d="M7 12v4c0 1.5 2.5 3 5 3s5-1.5 5-3v-4"/><path d="M21 9v6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M22 20v-1a5 5 0 0 0-3.5-4.8"/>',
        'upload' => '<path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V10"/><path d="M10 21v-6h4v6"/>',
        'award' => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 21l5-2.5L17 21l-1.5-7.5"/>',
        'book' => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v16H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H20"/>',
        'list' => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3.5 6h.01"/><path d="M3.5 12h.01"/><path d="M3.5 18h.01"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 3v4"/><path d="M16 3v4"/>',
        'calendarCheck' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M9 15l2 2 4-4"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
        'table' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M3 15h18"/><path d="M9 4v16"/>',
        'scroll' => '<path d="M5 4h11a2 2 0 0 1 2 2v12a2 2 0 0 0 2 2H8a2 2 0 0 1-2-2V5a1 1 0 0 0-1-1z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        'clipboard' => '<path d="M9 4H7a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'alert' => '<path d="M10.3 3.9L1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'trophy' => '<path d="M8 3h8v6a4 4 0 0 1-8 0z"/><path d="M8 5H5v2a3 3 0 0 0 3 3"/><path d="M16 5h3v2a3 3 0 0 1-3 3"/><path d="M12 13v4"/><path d="M8 21h8"/><path d="M9 17h6"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/>',
        'timer' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5"/><path d="M9 2h6"/>',
        'shield' => '<path d="M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5z"/><path d="M9 12l2 2 4-4"/>',
        'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01"/><path d="M18 12h.01"/>',
        'receipt' => '<path d="M5 3h14v18l-2.3-1.6-2.3 1.6-2.4-1.6L9.6 21l-2.3-1.6L5 21z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        'percent' => '<path d="M19 5L5 19"/><circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
        'dollar' => '<path d="M12 2v20"/><path d="M17 6.5A4 4 0 0 0 13 5h-2a3 3 0 0 0 0 6h2a3 3 0 0 1 0 6h-2a4 4 0 0 1-4-1.5"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
        'userCheck' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/><path d="M16 19l2 2 4-4"/>',
        'library' => '<path d="M4 3h4v18H4z"/><path d="M10 3h4v18h-4z"/><path d="M16.5 4.2l3.4 17"/><path d="M16.5 4.2l-1.6 8"/>',
        'bookMarked' => '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/><path d="M9 8h6"/>',
        'bus' => '<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 19v-3"/><path d="M17 19v-3"/><path d="M7 16h10"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'truck' => '<rect x="1" y="6" width="13" height="10" rx="1"/><path d="M14 9h4l3 3.5V16h-7z"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>',
        'bed' => '<path d="M3 18v-9"/><path d="M3 12h18v6"/><path d="M21 18v-4"/><circle cx="7.5" cy="9.5" r="2.5"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/>',
        'message' => '<path d="M21 11.5a8 8 0 0 1-8.5 8 9 9 0 0 1-4-.9L3 20l1.4-4.4A8 8 0 1 1 21 11.5z"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3"/><path d="M12 19v3"/><path d="M4.2 4.2l2.1 2.1"/><path d="M17.7 17.7l2.1 2.1"/><path d="M2 12h3"/><path d="M19 12h3"/><path d="M4.2 19.8l2.1-2.1"/><path d="M17.7 6.3l2.1-2.1"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h.01"/><path d="M15 8h.01"/><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 21v-5h4v5"/>',
        'chevron' => '<path d="M6 9l6 6 6-6"/>',
        'menu' => '<path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/>',
        'close' => '<path d="M18 6L6 18"/><path d="M6 6l12 12"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'check' => '<path d="M20 6L9 17l-5-5"/>',
        'sparkle' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M18.5 16.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8z"/>',
    ];

    /**
     * Render an icon as an inline SVG string.
     *
     * Unknown icon names fall back to a neutral dot so a typo can never blank
     * out a navigation row.
     */
    public static function render(string $name, string $classes = 'h-4 w-4'): string
    {
        $body = self::PATHS[$name] ?? '<circle cx="12" cy="12" r="3"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" '
            . 'stroke-linejoin="round" aria-hidden="true" class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">'
            . $body
            . '</svg>';
    }
}
