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
Schema::create('franchise_contracts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('franchise_id')->constrained('franchise_companies')->onDelete('cascade');
    $table->integer('contract_duration');
    $table->date('start_date');
    $table->date('end_date');
    $table->decimal('reality_fees', 15, 2);
    $table->integer('unite_no')->default(1);
    $table->string('contract_file')->nullable();
    $table->text('notes')->nullable();
    $table->enum('status', ['active', 'expired', 'pending'])->default('active');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_contracts');
    }
};
