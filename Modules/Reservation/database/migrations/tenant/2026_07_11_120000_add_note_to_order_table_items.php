<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_table_items', function (Blueprint $table) {
            $table->text('note')->nullable()->after('total_before_vat');
        });
    }

    public function down(): void
    {
        Schema::table('order_table_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
