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
        ];

        $inner = $paths[$name] ?? $paths['user'];

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    }

    private static function badge(mixed $badge): void
    {
        if ($badge === null || $badge === '') {
            return;
        }

        echo '<span class="store-icon-badge">' . htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
