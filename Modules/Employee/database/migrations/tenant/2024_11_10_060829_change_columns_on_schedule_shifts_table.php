<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_shifts', function (Blueprint $table) {
            $table->renameColumn('brake_duration', 'break_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_shifts', function (Blueprint $table) {
            $table->renameColumn('break_duration', 'brake_duration');

        });
    }
};
