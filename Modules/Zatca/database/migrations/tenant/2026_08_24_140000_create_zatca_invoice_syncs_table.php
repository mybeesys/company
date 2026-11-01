<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zatca_invoice_syncs')) {
            return;
        }

        Schema::create('zatca_invoice_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->unique();
            $table->string('report_type', 8)->nullable(); // B2C | B2B
            $table->string('status', 20)->default('pending'); // pending | synced | failed
            $table->text('last_error')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('invoice_hash')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        if (Schema::hasTable('transactions')) {
            try {
                Schema::table('zatca_invoice_syncs', function (Blueprint $table) {
                    $table->foreign('transaction_id')
                        ->references('id')
                        ->on('transactions')
                        ->cascadeOnDelete();
                });
            } catch (\Throwable) {
                // Skip FK when the referenced table/engine/types cannot accept it.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_invoice_syncs');
    }
};
