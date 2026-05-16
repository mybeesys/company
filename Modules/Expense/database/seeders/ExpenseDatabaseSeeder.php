<?php

declare(strict_types=1);

namespace Modules\Expense\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Expense\Models\ExpenseCategory;

class ExpenseDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['General', 'Utilities', 'Rent'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
