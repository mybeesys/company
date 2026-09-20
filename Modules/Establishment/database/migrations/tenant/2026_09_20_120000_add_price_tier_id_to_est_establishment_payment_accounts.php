<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        if (! Schema::hasColumn('est_establishment_payment_accounts', 'price_tier_id')) {
            Schema::table('est_establishment_payment_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('price_tier_id')->nullable()->after('account_id');
            });
        }

        if (
            Schema::hasTable('price_tiers')
            && Schema::hasColumn('est_establishment_payment_accounts', 'price_tier_id')
        ) {
            Schema::table('est_establishment_payment_accounts', function (Blueprint $table) {
                $table->foreign('price_tier_id')
                    ->references('id')
                    ->on('price_tiers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        if (! Schema::hasColumn('est_establishment_payment_accounts', 'price_tier_id')) {
            return;
        }

        Schema::table('est_establishment_payment_accounts', function (Blueprint $table) {
            $table->dropForeign(['price_tier_id']);
            $table->dropColumn('price_tier_id');
        });
    }
};
