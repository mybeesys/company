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
        Schema::create('franchise_contract_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('franchise_contracts')->onDelete('cascade');
            $table->integer('added_months');
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->foreignId('created_by')->nullable()->constrained('emp_employees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
