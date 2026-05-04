<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_inventory_items', function (Blueprint $table) {
            $table->string('unit_label', 191)->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('periodic_inventory_items', function (Blueprint $table) {
            $table->dropColumn('unit_label');
        });
    }
};
