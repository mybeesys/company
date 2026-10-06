<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        if (Schema::hasColumn('est_establishment_service_fees', 'show_on_invoice')) {
            return;
        }

        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            $after = Schema::hasColumn('est_establishment_service_fees', 'taxable')
                ? 'taxable'
                : (Schema::hasColumn('est_establishment_service_fees', 'is_active') ? 'is_active' : null);
            $col = $table->boolean('show_on_invoice')->default(true);
            if ($after) {
                $col->after($after);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }
        if (! Schema::hasColumn('est_establishment_service_fees', 'show_on_invoice')) {
            return;
        }

        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            $table->dropColumn('show_on_invoice');
        });
    }
};
