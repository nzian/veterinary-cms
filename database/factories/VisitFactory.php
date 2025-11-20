<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'visit_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'pet_id' => Pet::factory(),
            'user_id' => User::factory(),
            'weight' => $this->faker->randomFloat(2, 1, 50),
            'temperature' => $this->faker->randomFloat(1, 36, 40),
            'patient_type' => $this->faker->randomElement(['admission', 'outpatient', 'boarding']),
            'visit_status' => 'pending',
            'workflow_status' => 'In Progress',
        ];
    }
}
