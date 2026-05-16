<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            /** @var array<int, array{percent: float}> */
            $table->json('taxes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_tax_profiles');
    }
};
