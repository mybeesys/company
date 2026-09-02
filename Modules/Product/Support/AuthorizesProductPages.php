<?php

namespace Modules\Product\Support;

use Illuminate\Routing\Controllers\Middleware;

trait AuthorizesProductPages
{
    abstract protected static function productAuthEntity(): string;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        $crud = ProductPermissions::crud(static::productAuthEntity());
        $stack = [];

        if (isset($crud['show'])) {
            $stack[] = new Middleware('dashboard.perm:'.$crud['show'], only: ['index']);
        }

        if (isset($crud['create'])) {
            $stack[] = new Middleware('dashboard.perm:'.$crud['create'], only: ['create']);
        }

        if (isset($crud['update'])) {
            $stack[] = new Middleware('dashboard.perm:'.$crud['update'], only: ['edit']);
        }

        return $stack;
    }
}
