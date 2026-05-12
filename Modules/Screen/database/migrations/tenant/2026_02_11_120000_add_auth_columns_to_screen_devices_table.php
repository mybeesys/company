<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screen_devices', function (Blueprint $table) {
            $table->string('pin_hash')->nullable()->after('code');
            $table->string('pairing_token_hash', 64)->nullable()->after('pin_hash');
            $table->index('pairing_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('screen_devices', function (Blueprint $table) {
            $table->dropIndex(['pairing_token_hash']);
            $table->dropColumn(['pin_hash', 'pairing_token_hash']);
        });
    }
};
