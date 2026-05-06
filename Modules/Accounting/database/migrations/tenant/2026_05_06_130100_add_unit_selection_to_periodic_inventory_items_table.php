<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('periodic_inventory_items', 'unit_transfer_id')) {
                $table->unsignedBigInteger('unit_transfer_id')->nullable()->after('unit_label');
            }
            if (! Schema::hasColumn('periodic_inventory_items', 'unit_factor')) {
                $table->decimal('unit_factor', 15, 6)->nullable()->after('unit_transfer_id');
            }
            if (! Schema::hasColumn('periodic_inventory_items', 'physical_quantity_input')) {
                $table->decimal('physical_quantity_input', 15, 3)->nullable()->after('unit_factor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodic_inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('periodic_inventory_items', 'physical_quantity_input')) {
                $table->dropColumn('physical_quantity_input');
            }
            if (Schema::hasColumn('periodic_inventory_items', 'unit_factor')) {
                $table->dropColumn('unit_factor');
            }
            if (Schema::hasColumn('periodic_inventory_items', 'unit_transfer_id')) {
                $table->dropColumn('unit_transfer_id');
            }
        });
    }
};

