<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zatca_invoice_syncs')) {
            return;
        }

        Schema::table('zatca_invoice_syncs', function (Blueprint $table) {
            if (! Schema::hasColumn('zatca_invoice_syncs', 'synced_environment')) {
                $table->string('synced_environment', 16)->nullable()->after('report_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('zatca_invoice_syncs')) {
            return;
        }

        Schema::table('zatca_invoice_syncs', function (Blueprint $table) {
            if (Schema::hasColumn('zatca_invoice_syncs', 'synced_environment')) {
                $table->dropColumn('synced_environment');
            }
        });
    }
};
