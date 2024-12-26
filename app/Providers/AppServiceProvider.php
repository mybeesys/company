<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Models\Role;
use Modules\Employee\Policies\DashboardRolePolicy;
use Modules\Employee\Policies\PosRolePolicy;

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

        Gate::policy(Role::class, PosRolePolicy::class);
        Gate::policy(Role::class, DashboardRolePolicy::class);
    }
}