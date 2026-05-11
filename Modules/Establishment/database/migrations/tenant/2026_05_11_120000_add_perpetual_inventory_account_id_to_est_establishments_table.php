<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            $table->foreignId('perpetual_inventory_account_id')
                ->nullable()
                ->after('is_active')
                ->constrained('accounting_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            $table->dropForeign(['perpetual_inventory_account_id']);
            $table->dropColumn('perpetual_inventory_account_id');
        });
    }
};
