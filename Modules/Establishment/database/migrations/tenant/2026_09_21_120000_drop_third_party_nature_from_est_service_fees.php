<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        if (! Schema::hasColumn('est_establishment_service_fees', 'accounting_nature')) {
            return;
        }

        DB::table('est_establishment_service_fees')
            ->where('accounting_nature', 'THIRD_PARTY_LIABILITY')
            ->update([
                'accounting_nature' => 'OWN_REVENUE',
                'fee_direction' => DB::raw("COALESCE(fee_direction, 'COLLECTED')"),
                'posting_event' => DB::raw("COALESCE(posting_event, 'INVOICE')"),
            ]);

        if (Schema::hasColumn('est_establishment_service_fees', 'liability_account_id')) {
            DB::table('est_establishment_service_fees')
                ->whereNotNull('liability_account_id')
                ->update(['liability_account_id' => null]);
        }
    }

    public function down(): void
    {
        // Irreversible data cleanup; column retained for schema compatibility.
    }
};
