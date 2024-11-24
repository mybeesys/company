<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_product_inventories', function (Blueprint $table) {
            $table->dropForeign(['unit_id']); 
            $table->dropForeign(['primary_vendor_unit_id']); 
            $table->foreign('unit_id')
                  ->references('id')
                  ->on('product_unit_transfer');
            $table->foreign('primary_vendor_unit_id')
                  ->references('id')
                  ->on('product_unit_transfer');
        });
        Schema::table('inventory_purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']); 
            $table->foreign('unit_id')
                  ->references('id')
                  ->on('product_unit_transfer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            
        });
    }
};
