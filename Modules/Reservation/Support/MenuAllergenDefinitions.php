<?php

namespace Modules\Reservation\Support;

final class MenuAllergenDefinitions
{
    public const KEY_ICON_MAP = [
        'eggs' => 'fa-solid fa-egg',
        'milk' => 'fa-solid fa-bottle-droplet',
        'fish' => 'fa-solid fa-fish',
        'crustaceans' => 'fa-solid fa-shrimp',
        'tree_nuts' => 'fa-solid fa-tree',
        'peanuts' => 'fa-solid fa-bowl-food',
        'wheat' => 'fa-solid fa-wheat-awn',
        'soybeans' => 'fa-solid fa-bottle-water',
        'sesame' => 'fa-solid fa-mortar-pestle',
        'mustard' => 'fa-solid fa-jar',
        'celery' => 'fa-solid fa-carrot',
        'lupin' => 'fa-solid fa-seedling',
        'molluscs' => 'fa-solid fa-water',
        'sulphites' => 'fa-solid fa-flask',
    ];

    /** @return list<string> */
    public static function allowedKeys(): array
    {
        return array_keys(self::KEY_ICON_MAP);
    }

    /**
     * @param  list<string>|array<int, string>  $keys
     * @return list<string>|null null يعني «كل المفاتيح» (القيمة الافتراضية في قاعدة البيانات)
     */
    public static function normalizeStoredKeys(array $keys): ?array
    {
        $allowed = self::allowedKeys();
        $filtered = array_values(array_unique(array_intersect($allowed, array_map('strval', $keys))));
        sort($filtered);
        if (count($filtered) === count($allowed)) {
            return null;
        }

        return $filtered;
    }

    /**
     * @param  list<string>|null  $stored  null = اعرض الكل
     * @return array<string, string>  مفتاح => صنف أيقونة FontAwesome
     */
    public static function filterMapForDisplay(?array $stored): array
    {
        $full = self::KEY_ICON_MAP;
        if ($stored === null) {
            return $full;
        }
        if ($stored === []) {
            return [];
        }

        return array_intersect_key($full, array_flip($stored));
    }
}
