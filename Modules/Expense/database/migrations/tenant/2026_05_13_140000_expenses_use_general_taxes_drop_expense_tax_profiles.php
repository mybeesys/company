<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['tax_profile_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('tax_profile_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->after('expense_category_id')->constrained('taxes')->nullOnDelete();
        });

        Schema::dropIfExists('expense_tax_profiles');
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropColumn('tax_id');
        });

        Schema::create('expense_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('taxes');
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('tax_profile_id')->nullable()->after('expense_category_id')->constrained('expense_tax_profiles')->nullOnDelete();
        });
    }
};
