<?php

namespace Modules\Inventory\Support;

use Illuminate\Routing\Controllers\Middleware;

trait AuthorizesInventoryPages
{
    abstract protected static function inventoryAuthEntity(): string;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        $crud = InventoryPermissions::crud(static::inventoryAuthEntity());
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
