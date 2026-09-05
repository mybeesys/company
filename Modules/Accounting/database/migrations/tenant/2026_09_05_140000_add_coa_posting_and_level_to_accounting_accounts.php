<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
                $table->boolean('allow_direct_posting')->default(true)->after('status');
            }
            if (! Schema::hasColumn('accounting_accounts', 'coa_level')) {
                $table->unsignedTinyInteger('coa_level')->nullable()->after('allow_direct_posting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_accounts', 'coa_level')) {
                $table->dropColumn('coa_level');
            }
            if (Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
                $table->dropColumn('allow_direct_posting');
            }
        });
    }
};
