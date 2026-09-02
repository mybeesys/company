<?php

namespace Modules\Employee\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\PosRole;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeCard;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Employee\Policies\DashboardRolePolicy;
use Modules\Employee\Policies\EmployeePolicy;
use Modules\Employee\Policies\PayrollAdjustmentPolicy;
use Modules\Employee\Policies\PayrollGroupPolicy;
use Modules\Employee\Policies\PayrollPolicy;
use Modules\Employee\Policies\PosRolePolicy;
use Modules\Employee\Policies\ShiftPolicy;
use Modules\Employee\Policies\TimeCardPolicy;
use Modules\Employee\Policies\TimeSheetRulePolicy;
use Nwidart\Modules\Traits\PathNamespace;

class EmployeeServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Employee';

    protected string $nameLower = 'employee';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->app['router']->aliasMiddleware(
            'dashboard.perm',
            \Modules\Employee\Http\Middleware\EnsureDashboardPermission::class
        );

        Blade::if('dashboardcan', function (string|array $permissions) {
            return \Modules\Employee\Support\DashboardAccess::allows(auth()->user(), $permissions);
        });

        $this->registerPolicies();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Employee\Console\SyncEmployeePermissionsCommand::class,
        ]);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(PosRole::class, PosRolePolicy::class);
        Gate::policy(DashboardRole::class, DashboardRolePolicy::class);
        Gate::policy(PayrollAdjustment::class, PayrollAdjustmentPolicy::class);
        Gate::policy(TimeSheetRule::class, TimeSheetRulePolicy::class);
        Gate::policy(TimeCard::class, TimeCardPolicy::class);
        Gate::policy(Shift::class, ShiftPolicy::class);
        Gate::policy(Payroll::class, PayrollPolicy::class);
        Gate::policy(PayrollGroup::class, PayrollGroupPolicy::class);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), $this->nameLower);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
