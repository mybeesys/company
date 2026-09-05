<?php

namespace Modules\Accounting\Support;

final class MyBeeMasterCoaCatalog
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * @return array{
     *     version: string,
     *     pack: string,
     *     source: string,
     *     types: list<array<string, mixed>>,
     *     accounts: list<array<string, mixed>>,
     *     account_categories: array<string, string>,
     *     routing_gl_candidates: array<string, list<string>>,
     *     color_system: array<string, list<string>>
     * }
     */
    public static function get(): array
    {
        if (self::$cache === null) {
            $path = module_path('Accounting', 'data/mybee-master-coa-v5.php');
            /** @var array<string, mixed> $catalog */
            $catalog = require $path;
            self::$cache = $catalog;
        }

        return self::$cache;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function types(): array
    {
        return self::get()['types'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function accounts(): array
    {
        return self::get()['accounts'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function routingGlCandidates(): array
    {
        return self::get()['routing_gl_candidates'];
    }
}
