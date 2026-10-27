<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_payments')) {
            return;
        }

        DB::statement('ALTER TABLE `transaction_payments` MODIFY `transaction_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_payments')) {
            return;
        }

        DB::statement('ALTER TABLE `transaction_payments` MODIFY `transaction_id` BIGINT UNSIGNED NOT NULL');
    }
};
