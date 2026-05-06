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
        Schema::table('accounting_acc_trans_mappings', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_acc_trans_mappings', 'path_file')) {
                $table->string('path_file')->nullable()->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_acc_trans_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_acc_trans_mappings', 'path_file')) {
                $table->dropColumn('path_file');
            }
        });
    }
};
