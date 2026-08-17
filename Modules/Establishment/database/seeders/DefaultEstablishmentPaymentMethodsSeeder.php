<?php

namespace Modules\Establishment\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstablishmentPaymentAccount;

/**
 * Seeds unified default cashier payment methods:
 * نقداً / بطاقة / طلبات توصيل — then assigns them to branches.
 */
class DefaultEstablishmentPaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        $establishmentIds = Establishment::query()->notMain()->orderBy('id')->pluck('id');
        foreach ($establishmentIds as $establishmentId) {
            self::seedForEstablishment((int) $establishmentId);
        }
    }

    /** Idempotent: attaches global default methods to a branch, creating the catalog row if missing. */
    public static function seedForEstablishment(int $establishmentId): void
    {
        if ($establishmentId <= 0 || ! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        $establishment = Establishment::query()->find($establishmentId);
        if (! $establishment || $establishment->is_main) {
            return;
        }

        $seeder = new self;
        $hasAssignmentPivot = Schema::hasTable('est_payment_account_establishment');

        foreach ($seeder->defaultMethods() as $method) {
            $existing = EstablishmentPaymentAccount::query()
                ->where('payment_method_key', $method['payment_method_key'])
                ->orderBy('id')
                ->first();

            if (! $existing) {
                $existing = EstablishmentPaymentAccount::query()->create([
                    'establishment_id' => $establishmentId,
                    'payment_method_key' => $method['payment_method_key'],
                    'name_ar' => $method['name_ar'],
                    'name_en' => $method['name_en'],
                    'account_id' => $method['account_id'],
                ]);
            }

            if ($hasAssignmentPivot) {
                $pivot = [];
                if (Schema::hasColumn('est_payment_account_establishment', 'account_id')) {
                    $already = $existing->assignedEstablishments()
                        ->where('est_establishments.id', $establishmentId)
                        ->first();
                    $pivotAccountId = (int) ($already?->pivot?->account_id ?: $method['account_id'] ?: $existing->account_id ?: 0) ?: null;
                    $pivot = ['account_id' => $pivotAccountId];
                }

                $existing->assignedEstablishments()->syncWithoutDetaching([
                    $establishmentId => $pivot,
                ]);
                if (! $existing->establishment_id) {
                    $existing->update(['establishment_id' => $establishmentId]);
                }

                continue;
            }

            $alreadyOnBranch = EstablishmentPaymentAccount::query()
                ->where('establishment_id', $establishmentId)
                ->where('payment_method_key', $method['payment_method_key'])
                ->exists();

            if (! $alreadyOnBranch) {
                EstablishmentPaymentAccount::query()->create([
                    'establishment_id' => $establishmentId,
                    'payment_method_key' => $method['payment_method_key'],
                    'name_ar' => $method['name_ar'],
                    'name_en' => $method['name_en'],
                    'account_id' => $method['account_id'],
                ]);
            }
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
                'name_en' => 'Cash',
                'account_id' => $this->resolveAccountId('cash', ['111']),
            ],
            [
                'payment_method_key' => 'card',
                'name_ar' => 'بطاقة',
                'name_en' => 'Card',
                'account_id' => $this->resolveAccountId('card', ['112', '111']),
            ],
            [
                'payment_method_key' => 'delivery_apps',
                'name_ar' => 'طلبات توصيل',
                'name_en' => 'Delivery orders',
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
