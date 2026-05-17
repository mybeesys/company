<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollGroup;
use Modules\Establishment\Policies\CompanyPolicy;
use Modules\General\Models\NotificationSettingParameter;
use Spatie\TranslationLoader\TranslationLoaderManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindAppLangPath();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->rebindTranslationLoaderWithAppPaths();

        Blade::directive('format_currency', function ($expression) {
            return "<?php echo App\\Helpers\\CurrencyHelper::format_currency($expression); ?>";
        });

        Blade::directive('format_accounting_amount', function ($expression) {
            return "<?php echo App\\Helpers\\CurrencyHelper::format_accounting_amount($expression); ?>";
        });

        Blade::directive('getTax', function ($expression) {
            return "<?php echo App\\Helpers\\TaxHelper::getTax(...explode(',', $expression)); ?>";
        });

        Blade::directive('get_format_currency', function () {
            return '<?php echo App\\Helpers\\CurrencyHelper::get_format_currency(); ?>';
        });

        Gate::define('viewPayrolls', function ($user) {
            return $user->can('viewAny', Payroll::class) ||
                $user->can('viewAny', PayrollGroup::class);
        });

        // One listener only: TenancyBootstrapped used to stack a new MessageSending listener on every tenant init.
        Event::listen(MessageSending::class, function () {
            if (function_exists('tenancy') && tenancy()->tenant) {
                self::configureTenantMail();
            }
        });

        Gate::policy(Company::class, CompanyPolicy::class);
    }

    /**
     * Default Laravel lang path + Spatie package order can leave the loader with a
     * single path that does not resolve on some Linux/deploy setups. Force the same
     * path list as Illuminate's TranslationServiceProvider: framework defaults + app.
     */
    protected function bindAppLangPath(): void
    {
        $primary = base_path('resources/lang');
        if (is_dir($primary)) {
            $this->app->useLangPath($primary);

            return;
        }

        $fallback = $this->app->resourcePath('lang');
        if (is_dir($fallback)) {
            $this->app->useLangPath($fallback);
        }
    }

    protected function rebindTranslationLoaderWithAppPaths(): void
    {
        $appLang = $this->app->langPath();
        if (! is_dir($appLang)) {
            $appLang = base_path('resources/lang');
            if (is_dir($appLang)) {
                $this->app->useLangPath($appLang);
            } else {
                $alt = $this->app->resourcePath('lang');
                if (is_dir($alt)) {
                    $this->app->useLangPath($alt);
                }
            }
        }

        $appLang = $this->app->langPath();
        $frameworkLang = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang');

        $paths = array_values(array_filter([
            is_dir($frameworkLang) ? $frameworkLang : null,
            is_dir($appLang) ? $appLang : null,
        ]));

        if ($paths === []) {
            return;
        }

        $managerClass = (string) config(
            'translation-loader.translation_manager',
            TranslationLoaderManager::class
        );

        $this->app->forgetInstance('translator');
        $this->app->forgetInstance('translation.loader');

        $this->app->singleton('translation.loader', function ($app) use ($managerClass, $paths) {
            return new $managerClass($app['files'], $paths);
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $translator = new Translator($loader, $app->getLocale());
            $translator->setFallback($app->getFallbackLocale());

            return $translator;
        });
    }

    protected static function configureTenantMail()
    {
        try {
            $mailSettings = NotificationSettingParameter::all();
            Config::set('mail.default', $mailSettings->firstWhere('key', 'MAIL_MAILER')?->value ?? env('MAIL_MAILER', 'log'));
            Config::set('mail.mailers.smtp.host', $mailSettings->firstWhere('key', 'MAIL_HOST')?->value ?? env('MAIL_HOST', '127.0.0.1'));
            Config::set('mail.mailers.smtp.port', $mailSettings->firstWhere('key', 'MAIL_PORT')?->value ?? env('MAIL_PORT', 2525));
            Config::set('mail.mailers.smtp.username', $mailSettings->firstWhere('key', 'MAIL_USERNAME')?->value ?? env('MAIL_USERNAME'));
            Config::set('mail.mailers.smtp.password', $mailSettings->firstWhere('key', 'MAIL_PASSWORD')?->value ?? env('MAIL_PASSWORD'));
            Config::set('mail.mailers.smtp.encryption', $mailSettings->firstWhere('key', 'MAIL_ENCRYPTION')?->value ?? env('MAIL_ENCRYPTION', 'tls'));
            Config::set('mail.from.address', $mailSettings->firstWhere('key', 'MAIL_FROM_ADDRESS')?->value ?? env('MAIL_FROM_ADDRESS'));
            Config::set('mail.from.name', $mailSettings->firstWhere('key', 'MAIL_FROM_NAME')?->value ?? env('MAIL_FROM_NAME'));
        } catch (\Exception $e) {
            \Log::error('Failed to load tenant mail settings: '.$e->getMessage());
        }
    }
}
