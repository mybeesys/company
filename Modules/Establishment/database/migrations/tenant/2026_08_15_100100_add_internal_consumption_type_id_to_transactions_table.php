<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'internal_consumption_type_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('internal_consumption_type_id')->nullable()->after('purpose');
                $table->index('internal_consumption_type_id', 'transactions_ic_type_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'internal_consumption_type_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex('transactions_ic_type_id_index');
                $table->dropColumn('internal_consumption_type_id');
            });
        }
    }
};
