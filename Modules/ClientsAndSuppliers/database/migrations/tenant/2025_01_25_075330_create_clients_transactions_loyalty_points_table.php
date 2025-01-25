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
        Schema::create('cs_clients_transactions_loyalty_points', function (Blueprint $table) {
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('cs_contacts')->cascadeOnDelete();
            $table->unsignedInteger('points');
            $table->unique(['transaction_id', 'client_id'], 'clients_trans_points_transaction_id_client_id_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients_transactions_loyalty_points');
    }
};
