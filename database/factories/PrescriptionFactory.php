<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Pet;
use App\Models\User;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'pet_id' => Pet::inRandomOrder()->first()->pet_id,
            'prescription_date' => $this->faker->date(),
            'medication' => $this->faker->sentence(),
            'notes' => $this->faker->optional()->sentence(),
            'user_id' => User::inRandomOrder()->first()->user_id,
            'branch_id' => Branch::inRandomOrder()->first()->branch_id,
            'differential_diagnosis' => $this->faker->optional()->sentence(),
        ];
    }
}