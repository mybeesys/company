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
        Schema::create('sales_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->unique();
            $table->string('value_type')->default('fixed');
            $table->decimal('value');
            $table->dateTime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->integer('person_use_time_count')->default(0);
            $table->integer('coupon_count')->default(0);
            $table->string('discount_apply_to')->nullable();
            $table->boolean('apply_to_clients_groups')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_coupons');
    }
};
