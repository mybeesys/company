<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('format_currency', function ($expression) {
            return "<?php echo App\\Helpers\\CurrencyHelper::format_currency($expression); ?>";
        });

        Blade::directive('get_format_currency', function () {
            return "<?php echo App\\Helpers\\CurrencyHelper::get_format_currency(); ?>";
        });
    }
}