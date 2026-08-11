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

        Schema::table('est_establishment_payment_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('est_establishment_payment_accounts', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('payment_method_key');
            }
            if (! Schema::hasColumn('est_establishment_payment_accounts', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_establishment_payment_accounts')) {
            return;
        }

        Schema::table('est_establishment_payment_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('est_establishment_payment_accounts', 'name_en')) {
                $table->dropColumn('name_en');
            }
            if (Schema::hasColumn('est_establishment_payment_accounts', 'name_ar')) {
                $table->dropColumn('name_ar');
            }
        });
    }
};
