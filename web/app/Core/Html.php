<?php

declare(strict_types=1);

namespace Store\Core;

class Html
{
    public static function tooltipStart(string $label): void
    {
        $json = json_encode($label, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        echo '<span class="relative inline-flex" x-data=\'storeTooltip(' . $json . ')\' @mouseenter="show()" @mouseleave="hide()" @focusin="show(true)" @focusout="hide()">';
    }

    public static function tooltipEnd(): void
    {
        echo '<template x-teleport="body"><span class="store-tooltip" role="tooltip" x-cloak x-show="open" x-transition.opacity.duration.100ms :style="style" x-text="label"></span></template></span>';
    }

    public static function timeAgo(?\DateTimeInterface $date, string $empty = '—'): void
    {
        if ($date === null) {
            echo htmlspecialchars($empty, ENT_QUOTES, 'UTF-8');

            return;
        }

        $absolute = DateFormat::absolute($date);
        $relative = DateFormat::relative($date);
        $iso = \Illuminate\Support\Carbon::instance($date)->toIso8601String();

        self::tooltipStart($absolute);
        echo '<time class="store-time" tabindex="0" datetime="' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($relative, ENT_QUOTES, 'UTF-8') . '</time>';
        self::tooltipEnd();
    }

    /** @param array<string, mixed> $options */
    public static function iconLink(string $href, string $icon, string $label, array $options = []): void
    {
        $active = (bool) ($options['active'] ?? false);
        $badge = $options['badge'] ?? null;
        $extraClass = (string) ($options['class'] ?? '');

        self::tooltipStart($label);
        echo '<a class="store-icon-btn' . ($active ? ' is-active' : '') . ($extraClass !== '' ? ' ' . $extraClass : '') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"' . ($active ? ' aria-current="page"' : '') . '>';
        echo self::iconSvg($icon);
        self::badge($badge);
        echo '</a>';
        self::tooltipEnd();
    }

    /** @param array<string, mixed> $options */
    public static function iconButton(string $icon, string $label, array $options = []): void
    {
        $type = (string) ($options['type'] ?? 'button');
        $extraClass = (string) ($options['class'] ?? '');
        $attrs = (string) ($options['attrs'] ?? '');
        $active = (bool) ($options['active'] ?? false);

        self::tooltipStart($label);
        echo '<button class="store-icon-btn' . ($active ? ' is-active' : '') . ($extraClass !== '' ? ' ' . $extraClass : '') . '" type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
        if ($attrs !== '') {
            echo ' ' . $attrs;
        }
        echo '>';
        echo self::iconSvg($icon);
        echo '</button>';
        self::tooltipEnd();
    }

    public static function iconSvg(string $name): string
    {
        $paths = [
            'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
            'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>',
            'package' => '<path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/>',
            'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
            'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
            'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
            'monitor' => '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
            'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'phone' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>',
            'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'layout' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
            'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'badge-check' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
        ];

        $inner = $paths[$name] ?? $paths['user'];

        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    }

    private static function badge(mixed $badge): void
    {
        if ($badge === null || $badge === '') {
            return;
        }

        echo '<span class="store-icon-badge">' . htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
