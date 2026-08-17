<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstablishmentPaymentAccount;

return new class extends Migration
{
    public function up(): void
    {
        $this->createPivot(
            'est_payment_account_establishment',
            'payment_account_id',
            'est_establishment_payment_accounts',
            'est_pay_acc_est_fk',
            'est_pay_acc_est_est_fk',
            'est_pay_acc_est_unique'
        );
        $this->createPivot(
            'est_internal_consumption_type_establishment',
            'internal_consumption_type_id',
            'est_establishment_internal_consumption_types',
            'est_ic_type_est_fk',
            'est_ic_type_est_est_fk',
            'est_ic_type_est_unique'
        );
        $this->createPivot(
            'est_service_fee_establishment',
            'service_fee_id',
            'est_establishment_service_fees',
            'est_sf_est_assign_fk',
            'est_sf_est_assign_est_fk',
            'est_sf_est_assign_unique'
        );

        $this->makeEstablishmentIdNullable('est_establishment_payment_accounts', 'est_pay_accounts_est_method_unique');
        $this->makeEstablishmentIdNullable('est_establishment_internal_consumption_types', 'est_ic_types_est_key_unique', 'est_ic_types_est_id_fk');
        $this->makeEstablishmentIdNullable('est_establishment_service_fees', null, 'est_sf_est_id_fk');

        $this->backfillPivot('est_establishment_payment_accounts', 'est_payment_account_establishment', 'payment_account_id');
        $this->backfillPivot('est_establishment_internal_consumption_types', 'est_internal_consumption_type_establishment', 'internal_consumption_type_id');
        $this->backfillPivot('est_establishment_service_fees', 'est_service_fee_establishment', 'service_fee_id');

        $this->ensureDefaultPaymentMethods();
    }

    public function down(): void
    {
        Schema::dropIfExists('est_service_fee_establishment');
        Schema::dropIfExists('est_internal_consumption_type_establishment');
        Schema::dropIfExists('est_payment_account_establishment');
    }

    private function createPivot(
        string $table,
        string $foreignKey,
        string $parentTable,
        string $parentFkName,
        string $estFkName,
        string $uniqueName
    ): void {
        if (Schema::hasTable($table) || ! Schema::hasTable($parentTable)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint) use ($foreignKey, $parentTable, $parentFkName, $estFkName, $uniqueName) {
            $blueprint->id();
            $blueprint->unsignedBigInteger($foreignKey);
            $blueprint->unsignedBigInteger('establishment_id');
            $blueprint->timestamps();

            $blueprint->unique([$foreignKey, 'establishment_id'], $uniqueName);
            $blueprint->foreign($foreignKey, $parentFkName)
                ->references('id')
                ->on($parentTable)
                ->cascadeOnDelete();
            $blueprint->foreign('establishment_id', $estFkName)
                ->references('id')
                ->on('est_establishments')
                ->cascadeOnDelete();
        });
    }

    private function makeEstablishmentIdNullable(string $table, ?string $uniqueName, ?string $foreignName = null): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'establishment_id')) {
            return;
        }

        if ($uniqueName) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($uniqueName) {
                    $blueprint->dropUnique($uniqueName);
                });
            } catch (\Throwable) {
            }
        }

        $foreignNames = array_values(array_filter([
            $foreignName,
            $table.'_establishment_id_foreign',
        ]));

        foreach ($foreignNames as $name) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropForeign($name);
                });
            } catch (\Throwable) {
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['establishment_id']);
            });
        } catch (\Throwable) {
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `establishment_id` BIGINT UNSIGNED NULL");

        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('establishment_id')
                    ->references('id')
                    ->on('est_establishments')
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
        }
    }

    private function backfillPivot(string $sourceTable, string $pivotTable, string $foreignKey): void
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($pivotTable)) {
            return;
        }

        $now = now();
        $rows = DB::table($sourceTable)->whereNotNull('establishment_id')->get(['id', 'establishment_id']);
        foreach ($rows as $row) {
            DB::table($pivotTable)->insertOrIgnore([
                $foreignKey => (int) $row->id,
                'establishment_id' => (int) $row->establishment_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function ensureDefaultPaymentMethods(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        $branchIds = Establishment::query()->notMain()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $defaults = [
            ['payment_method_key' => 'cash', 'name_ar' => 'نقداً', 'name_en' => 'Cash', 'gl' => ['111']],
            ['payment_method_key' => 'card', 'name_ar' => 'بطاقة', 'name_en' => 'Card', 'gl' => ['112', '111']],
            ['payment_method_key' => 'delivery_apps', 'name_ar' => 'طلبات توصيل', 'name_en' => 'Delivery orders', 'gl' => ['112', '111']],
        ];

        foreach ($defaults as $method) {
            $existing = EstablishmentPaymentAccount::query()
                ->where('payment_method_key', $method['payment_method_key'])
                ->orderBy('id')
                ->first();

            if (! $existing) {
                $existing = EstablishmentPaymentAccount::query()->create([
                    'establishment_id' => $branchIds[0] ?? null,
                    'payment_method_key' => $method['payment_method_key'],
                    'name_ar' => $method['name_ar'],
                    'name_en' => $method['name_en'],
                    'account_id' => $this->resolveAccountId($method['gl']),
                ]);

                if ($branchIds !== []) {
                    $existing->assignedEstablishments()->syncWithoutDetaching($branchIds);
                }
            } elseif ($method['payment_method_key'] === 'delivery_apps' && ($existing->name_ar === 'تطبيقات توصيل' || $existing->name_ar === '')) {
                $existing->update([
                    'name_ar' => 'طلبات توصيل',
                    'name_en' => $existing->name_en ?: 'Delivery orders',
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $glCodes
     */
    private function resolveAccountId(array $glCodes): ?int
    {
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
};
