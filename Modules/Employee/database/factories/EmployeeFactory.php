<?php

namespace Modules\Employee\Database\Factories;

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
            'firstName' => fake()->name(),
            'lastName' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phoneNumber' => fake()->phoneNumber(),
            'PIN' => fake()->countryCode(),
            'employmentStartDate' => fake()->date(),
            'employmentEndDate' => fake()->date(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

