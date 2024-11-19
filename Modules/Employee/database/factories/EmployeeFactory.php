<?php

namespace Modules\Employee\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Employee\Models\Employee;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Employee::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'name_en' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'PIN' => fake()->countryCode(),
            'employment_start_date' => fake()->date(),
            'employment_end_date' => fake()->date(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

