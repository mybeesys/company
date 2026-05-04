<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_custommenu_times', function (Blueprint $table) {
            $table->boolean('no_date_limit')->default(false)->after('to_date');
        });
    }

    public function down(): void
    {
        Schema::table('product_custommenu_times', function (Blueprint $table) {
            $table->dropColumn('no_date_limit');
        });
    }
};
