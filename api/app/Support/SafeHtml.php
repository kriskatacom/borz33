<?php

declare(strict_types=1);

namespace App\Support;

class SafeHtml
{
    /** @var list<string> */
    private const BANNER_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'];

    public static function isBlank(string $html): bool
    {
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $plain) ?? '') === '';
    }

    public static function bannerText(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (!str_contains($html, '<')) {
            $escaped = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return '<p>' . nl2br($escaped, false) . '</p>';
        }

        $allow = '<' . implode('><', self::BANNER_TAGS) . '>';
        $html = strip_tags($html, $allow);
        $html = preg_replace('/<(\/?)([a-z0-9]+)(?:\s[^>]*)?>/i', '<$1$2>', $html) ?? $html;
        $html = preg_replace('/<(\/?)(?!(?:' . implode('|', self::BANNER_TAGS) . ')\b)[a-z0-9]+>/i', '', $html) ?? $html;

        return trim($html);
    }
}
