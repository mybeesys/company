<?php

namespace Modules\Establishment\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstablishmentPaymentAccount;

/**
 * Seeds unified default cashier payment methods for every establishment:
 * نقداً / بطاقة / تطبيقات توصيل — with standard GL accounts when available.
 */
class DefaultEstablishmentPaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        $establishmentIds = Establishment::query()->orderBy('id')->pluck('id');
        foreach ($establishmentIds as $establishmentId) {
            self::seedForEstablishment((int) $establishmentId);
        }
    }

    /** Idempotent: inserts only missing default keys for one branch. */
    public static function seedForEstablishment(int $establishmentId): void
    {
        if ($establishmentId <= 0 || ! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        $seeder = new self;
        foreach ($seeder->defaultMethods() as $method) {
            $exists = EstablishmentPaymentAccount::query()
                ->where('establishment_id', $establishmentId)
                ->where('payment_method_key', $method['payment_method_key'])
                ->exists();

            if ($exists) {
                continue;
            }

            EstablishmentPaymentAccount::query()->create([
                'establishment_id' => $establishmentId,
                'payment_method_key' => $method['payment_method_key'],
                'name_ar' => $method['name_ar'],
                'name_en' => $method['name_en'],
                'account_id' => $method['account_id'],
            ]);
        }
    }

    /**
     * @return list<array{payment_method_key: string, name_ar: string, name_en: string, account_id: int|null}>
     */
    private function defaultMethods(): array
    {
        return [
            [
                'payment_method_key' => 'cash',
                'name_ar' => 'نقداً',
                'name_en' => 'cash',
                'account_id' => $this->resolveAccountId('cash', ['111']),
            ],
            [
                'payment_method_key' => 'card',
                'name_ar' => 'بطاقة',
                'name_en' => 'card',
                'account_id' => $this->resolveAccountId('card', ['112', '111']),
            ],
            [
                'payment_method_key' => 'delivery_apps',
                'name_ar' => 'تطبيقات توصيل',
                'name_en' => 'delivery_apps',
                'account_id' => $this->resolveAccountId('delivery_apps', ['112', '111']),
            ],
        ];
    }

    /**
     * Prefer linked account on global payment_methods, then chart by gl_code.
     *
     * @param  list<string>  $glCodes
     */
    private function resolveAccountId(string $paymentMethodNameEn, array $glCodes): ?int
    {
        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'account_id')) {
            $fromGlobal = DB::table('payment_methods')
                ->where('name_en', $paymentMethodNameEn)
                ->whereNotNull('account_id')
                ->value('account_id');

            if ($fromGlobal) {
                return (int) $fromGlobal;
            }
        }

        foreach ($glCodes as $glCode) {
            $accountId = AccountingAccount::query()
                ->where('gl_code', $glCode)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '')->orWhere('status', 'active');
                })
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }
}
