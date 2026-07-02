<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_pairing_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('screen_devices')->cascadeOnDelete();
            $table->string('pin_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['pin_hash', 'used_at', 'expires_at']);
            $table->index(['device_id', 'used_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_pairing_pins');
    }
};
