<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Establishment\database\seeders\DefaultEstablishmentPaymentMethodsSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new DefaultEstablishmentPaymentMethodsSeeder)->run();
    }

    public function down(): void
    {
        // Keep seeded rows — operators may have customized them after seed.
    }
};
