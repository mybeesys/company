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
            $table->decimal('last_counted_quantity', 15, 3)->default(0);
            $table->date('last_counted_date')->nullable()->after('last_counted_quantity');
            $table->decimal('periodic_adjustment', 15, 3)->default(0)->after('last_counted_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_products', function (Blueprint $table) {
            $table->dropColumn(['last_counted_quantity', 'last_counted_date', 'periodic_adjustment']);
        });
    }
};