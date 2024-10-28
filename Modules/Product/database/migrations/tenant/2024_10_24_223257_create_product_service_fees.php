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
        Schema::create('product_service_fees', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->unique();
            $table->string('name_en')->unique();
            $table->decimal('amount');
            $table->tinyInteger('service_fee_type');
            $table->tinyInteger('application_type');
            $table->tinyInteger('calculation_method');
            $table->boolean('taxable');
            $table->boolean('active');
            $table->integer('minimum')->nullable();
            $table->tinyInteger('auto_apply_type')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->tinyInteger('creditType')->nullable();
            $table->integer('guestCount')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
