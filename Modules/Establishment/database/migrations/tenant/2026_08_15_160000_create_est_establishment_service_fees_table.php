<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        Schema::create('est_establishment_service_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establishment_id');
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('amount', 15, 4)->default(0);
            $table->string('service_fee_type', 10)->default('0');
            $table->string('application_type', 10)->default('1');
            $table->string('calculation_method', 10)->default('0');
            $table->boolean('taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('auto_apply_type', 10)->nullable();
            $table->json('dining_type_ids')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->unsignedBigInteger('cashier_payment_method_id')->nullable();
            $table->dateTime('from_date')->nullable();
            $table->dateTime('to_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('establishment_id', 'est_sf_est_id_fk')
                ->references('id')
                ->on('est_establishments')
                ->cascadeOnDelete();

            $table->foreign('cashier_payment_method_id', 'est_sf_pay_method_fk')
                ->references('id')
                ->on('est_establishment_payment_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('est_establishment_service_fees');
    }
};
