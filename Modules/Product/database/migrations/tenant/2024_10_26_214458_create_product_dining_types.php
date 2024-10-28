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
        Schema::table('product_service_fees', function (Blueprint $table) {
            $table->renameColumn('creditType', 'credit_type');
        });
        Schema::create('product_dining_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->unique();
            $table->string('name_en')->unique();
            $table->boolean('active');
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
        Schema::create('product_service_fee_dining_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_fee_id');
            $table->unsignedBigInteger('dining_type_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('service_fee_id')
                ->references('id')
                ->on('product_service_fees');
            $table->foreign('dining_type_id')
                ->references('id')
                ->on('product_dining_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
