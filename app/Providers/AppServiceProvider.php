<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollGroup;
use Modules\Establishment\Policies\CompanyPolicy;
use Modules\General\Models\NotificationSettingParameter;
use Stancl\Tenancy\Events\TenancyBootstrapped;

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

        Event::listen(TenancyBootstrapped::class, function (TenancyBootstrapped $event) {
            $this->configureMailTransport();
        });

        Gate::policy(Company::class, CompanyPolicy::class);
    }

    protected function configureMailTransport()
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            self::configureTenantMail();
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
            \Log::error('Failed to load tenant mail settings: ' . $e->getMessage());
        }
    }
}
