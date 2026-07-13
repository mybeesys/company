<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('transaction_id');
            $table->string('combo_id')->nullable()->after('modifier_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'combo_id']);
        });
    }
};
