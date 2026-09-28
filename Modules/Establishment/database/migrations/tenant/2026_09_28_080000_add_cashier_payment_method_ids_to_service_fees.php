<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow multiple branch payment methods per service fee (auto-apply by payment).
 * Keeps cashier_payment_method_id as first-id BC for older clients.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('est_establishment_service_fees', 'cashier_payment_method_ids')) {
                $table->json('cashier_payment_method_ids')->nullable()->after('cashier_payment_method_id');
            }
        });

        if (Schema::hasColumn('est_establishment_service_fees', 'cashier_payment_method_ids')) {
            DB::table('est_establishment_service_fees')
                ->whereNotNull('cashier_payment_method_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $existing = null;
                        if (! empty($row->cashier_payment_method_ids)) {
                            $decoded = json_decode((string) $row->cashier_payment_method_ids, true);
                            if (is_array($decoded) && $decoded !== []) {
                                continue;
                            }
                        }

                        DB::table('est_establishment_service_fees')
                            ->where('id', $row->id)
                            ->update([
                                'cashier_payment_method_ids' => json_encode([(int) $row->cashier_payment_method_id]),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            if (Schema::hasColumn('est_establishment_service_fees', 'cashier_payment_method_ids')) {
                $table->dropColumn('cashier_payment_method_ids');
            }
        });
    }
};
