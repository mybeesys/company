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
        Schema::table('product_products', function (Blueprint $table) {
          
        $table->boolean('sold_by_weight')->default(false);
        $table->boolean('track_serial_number')->default(false);
        $table->string('image')->nullable();
        $table->string('color')->nullable();
        $table->decimal('commissions')->nullable();
         });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_products', function (Blueprint $table) {
            $table->dropColumn('sold_by_weight');
            $table->dropColumn('track_serial_number');
            $table->dropColumn('image');
            $table->dropColumn('color');
            $table->dropColumn('decimal');
        });
        //
    }
};
