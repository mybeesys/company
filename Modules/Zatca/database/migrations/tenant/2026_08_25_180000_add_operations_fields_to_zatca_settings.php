<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zatca_settings')) {
            return;
        }

        Schema::table('zatca_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('zatca_settings', 'auto_sync_mode')) {
                $table->string('auto_sync_mode', 16)->default('disable')->after('status');
            }
            if (! Schema::hasColumn('zatca_settings', 'disable_discount')) {
                $table->boolean('disable_discount')->default(false)->after('auto_sync_mode');
            }
            if (! Schema::hasColumn('zatca_settings', 'disable_order_tax')) {
                $table->boolean('disable_order_tax')->default(false)->after('disable_discount');
            }
            if (! Schema::hasColumn('zatca_settings', 'default_sales_discount')) {
                $table->decimal('default_sales_discount', 8, 2)->default(0)->after('disable_order_tax');
            }
            if (! Schema::hasColumn('zatca_settings', 'lock_synced_invoices')) {
                $table->boolean('lock_synced_invoices')->default(true)->after('default_sales_discount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('zatca_settings')) {
            return;
        }

        Schema::table('zatca_settings', function (Blueprint $table) {
            foreach ([
                'auto_sync_mode',
                'disable_discount',
                'disable_order_tax',
                'default_sales_discount',
                'lock_synced_invoices',
            ] as $column) {
                if (Schema::hasColumn('zatca_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
