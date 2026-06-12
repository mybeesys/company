<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_pairing_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('pairing_id_hash', 64)->unique();
            $table->foreignId('device_id')->constrained('screen_devices')->cascadeOnDelete();
            $table->timestamp('linked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_pairing_sessions');
    }
};
