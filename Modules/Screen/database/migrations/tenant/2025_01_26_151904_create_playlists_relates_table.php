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
        Schema::create('screen_playlists_relates', function (Blueprint $table) {
            $table->foreignId('playlist_id')->constrained('screen_playlists')->cascadeOnDelete();
            $table->morphs('related');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_playlists_relates');
    }
};
