<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->boolean('is_first_year')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->string('name', 191);
            $table->unsignedTinyInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['financial_year_id', 'period_number']);
            $table->index(['financial_year_id', 'start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('financial_years');
    }
};
