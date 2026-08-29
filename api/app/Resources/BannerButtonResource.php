<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\BannerButton;

class BannerButtonResource
{
    /** @return array<string, mixed> */
    public static function toArray(BannerButton $button): array
    {
        return [
            'id' => $button->id,
            'label' => $button->label,
            'url' => $button->url,
            'open_in_new_tab' => $button->open_in_new_tab,
            'sort_order' => $button->sort_order,
        ];
    }

    /** @param iterable<int, BannerButton> $buttons */
    public static function collection(iterable $buttons): array
    {
        $items = [];

        foreach ($buttons as $button) {
            $items[] = self::toArray($button);
        }

        return $items;
    }
}
