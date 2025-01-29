<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    Modules\Product\Providers\RouteServiceProvider::class,
    Modules\Inventory\Providers\RouteServiceProvider::class,
    Modules\Inventory\Providers\InventoryServiceProvider::class,
    Modules\Reservation\Providers\RouteServiceProvider::class,
    Modules\Reservation\Providers\ReservationServiceProvider::class
];