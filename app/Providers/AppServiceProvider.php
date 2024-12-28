<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollGroup;

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

        Blade::directive('getTax', function ($expression) {
            return "<?php echo App\\Helpers\\TaxHelper::getTax(...explode(',', $expression)); ?>";
        });


        Blade::directive('get_format_currency', function () {
            return "<?php echo App\\Helpers\\CurrencyHelper::get_format_currency(); ?>";
        });

        Gate::define('viewPayrolls', function ($user) {
            return $user->can('viewAny', Payroll::class) || 
                   $user->can('viewAny', PayrollGroup::class);
        });
    }
}
