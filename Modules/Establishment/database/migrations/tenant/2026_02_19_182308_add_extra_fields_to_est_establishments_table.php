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
            $table->foreignId('franchise_id')->nullable()->after('code')
                ->constrained('franchise_companies')->onDelete('set null');
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
            $table->dropForeign(['franchise_id']);
            $table->dropColumn(['franchise_id', 'is_franchise', 'theme']);
        });
    }
};
