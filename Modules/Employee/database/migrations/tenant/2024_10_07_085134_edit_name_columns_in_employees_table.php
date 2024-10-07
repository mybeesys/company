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
        Schema::table('employee_employees', function (Blueprint $table) {
            $table->renameColumn('firstName', 'name');
            $table->renameColumn('lastName', 'name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_employees', function (Blueprint $table) {
            $table->renameColumn('name', 'firstName');
            $table->renameColumn('name_en', 'lastName');

        });
    }
};
