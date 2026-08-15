<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_internal_consumption_types')) {
            return;
        }

        if (! Schema::hasColumn('est_establishments', 'internal_consumption_expense_account_id')) {
            return;
        }

        $rows = DB::table('est_establishments')
            ->whereNotNull('internal_consumption_expense_account_id')
            ->get(['id', 'internal_consumption_expense_account_id']);

        foreach ($rows as $row) {
            $exists = DB::table('est_establishment_internal_consumption_types')
                ->where('establishment_id', $row->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('est_establishment_internal_consumption_types')->insert([
                'establishment_id' => $row->id,
                'type_key' => 'default_internal_consumption',
                'name_ar' => 'استهلاك داخلي',
                'name_en' => 'Internal consumption',
                'value_type' => 'cost',
                'value' => null,
                'account_id' => $row->internal_consumption_expense_account_id,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_establishment_internal_consumption_types')) {
            return;
        }

        DB::table('est_establishment_internal_consumption_types')
            ->where('type_key', 'default_internal_consumption')
            ->delete();
    }
};
