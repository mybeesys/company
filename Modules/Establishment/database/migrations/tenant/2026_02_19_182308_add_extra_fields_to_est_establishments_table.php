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
        Schema::table('est_establishments', function (Blueprint $table) {
            // No FK: franchise_companies may not exist yet (migration path order / optional Franchise module).
            $table->unsignedBigInteger('franchise_id')->nullable()->after('code');
            $table->index('franchise_id');
            $table->boolean('is_franchise')->default(0)->after('franchise_id');

            $table->string('theme')->nullable()->after('logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            $table->dropIndex(['franchise_id']);
            $table->dropColumn(['franchise_id', 'is_franchise', 'theme']);
        });
    }
};
