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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('gl_code');
            $table->string('account_primary_type')->nullable();
            $table->bigInteger('account_sub_type_id')->nullable();
            $table->string('account_category')->nullable();
            $table->string('account_type')->default('normal');
            $table->bigInteger('detail_type_id')->nullable();
            $table->bigInteger('parent_account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->integer('created_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');

  }
};