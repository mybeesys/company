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
        Schema::create('accounting_account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('account_primary_type');
            $table->string('account_type');
            $table->bigInteger('parent_id')->nullable();
            $table->string('description')->nullable();
            $table->tinyInteger('show_balance')->default(1);
            $table->integer('created_by')->nullable();
            $table->string('gl_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_account_types');
    }
};