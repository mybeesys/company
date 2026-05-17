<?php

declare(strict_types=1);

namespace Modules\Expense\Database\Seeders;

use Illuminate\Database\Seeder;

class ExpenseDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Expenses use chart of accounts directly; no category seed data.
    }
}
