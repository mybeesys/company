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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('name_ar')->after('name');
            $table->string('type')->after('name_ar');
            $table->text('description')->nullable()->after('type');
            $table->text('description_ar')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('name_ar');
            $table->dropColumn('type');
            $table->dropColumn('description');
            $table->dropColumn('description_ar');
        });
    }
};
