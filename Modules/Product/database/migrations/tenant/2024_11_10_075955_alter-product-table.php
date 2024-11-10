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
        //product_products
        Schema::table('product_products', function($table)
      {
        $table->boolean('group_combo')->nullable()->change();
        $table->boolean('set_price')->nullable()->change();
        $table->boolean('use_upcharge')->nullable()->change();
        $table->boolean('linked_combo')->nullable()->change();
        $table->tinyInteger('promot_upsell')->nullable()->change();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
