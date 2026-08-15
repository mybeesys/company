<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retry-safe: a failed first run may leave the table without foreign keys.
        if (Schema::hasTable('est_establishment_internal_consumption_types')) {
            Schema::drop('est_establishment_internal_consumption_types');
        }

        Schema::create('est_establishment_internal_consumption_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establishment_id');
            $table->string('type_key', 80);
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('value_type', 20)->default('cost');
            $table->decimal('value', 15, 4)->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('establishment_id', 'est_ic_types_est_id_fk')
                ->references('id')
                ->on('est_establishments')
                ->cascadeOnDelete();

            $table->foreign('account_id', 'est_ic_types_account_id_fk')
                ->references('id')
                ->on('accounting_accounts')
                ->nullOnDelete();

            $table->unique(
                ['establishment_id', 'type_key'],
                'est_ic_types_est_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('est_establishment_internal_consumption_types');
    }
};
