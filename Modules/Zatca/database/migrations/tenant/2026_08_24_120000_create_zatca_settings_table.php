<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton ZATCA Phase-2 configuration per tenant.
 * Stores CSR inputs, environment, and generated CSID credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zatca_settings')) {
            return;
        }

        Schema::create('zatca_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('zatca_environment', ['local', 'simulation', 'production'])->default('local');
            $table->text('zatca_app_key')->nullable(); // encrypted at model layer
            $table->string('seller_name');
            $table->string('vat_number', 15);
            $table->string('commercial_registration_number', 32);
            $table->string('organization_unit')->nullable();
            $table->string('organization_name');
            $table->string('country_code', 2)->default('SA');
            $table->string('city')->nullable();
            $table->string('building_number', 16)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('district')->nullable();
            $table->string('street_name')->nullable();
            $table->string('plot_identification', 32)->nullable();
            // CSR / onboarding extras required by Bl\FatooraZatca\Objects\Setting
            $table->string('email_address')->nullable();
            $table->string('otp', 32)->nullable();
            $table->string('common_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('egs_serial_number', 191)->nullable();
            $table->string('invoice_type', 8)->default('1100'); // BOTH
            $table->json('generated_credentials')->nullable();
            $table->text('last_error')->nullable();
            $table->enum('status', ['pending', 'configured', 'failed'])->default('pending');
            $table->timestamp('credentials_generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_settings');
    }
};
