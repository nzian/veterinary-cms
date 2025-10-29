<?php

namespace Database\Factories;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'pet_id' => null,
            'prescription_date' => $this->faker->date(),
            'medication' => $this->faker->sentence(),
            'notes' => $this->faker->optional()->sentence(),
            'user_id' => null,
            'branch_id' => null,
            'differential_diagnosis' => $this->faker->optional()->sentence(),
        ];
    }
}