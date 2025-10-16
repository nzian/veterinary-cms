<?php

namespace Database\Factories;

use App\Models\MedicalHistory;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalHistoryFactory extends Factory
{
    protected $model = MedicalHistory::class;

    public function definition(): array
    {
        return [
            'pet_id' => Pet::inRandomOrder()->first()->pet_id,
            'weight' => $this->faker->randomFloat(2,1,50),
            'temperature' => $this->faker->randomFloat(2,1,100),
            'visit_date' => $this->faker->date(),
            'diagnosis' => $this->faker->sentence(),
            'treatment' => $this->faker->sentence(),
            'medication' => $this->faker->sentence(),
            'veterinarian_name' => $this->faker->name(),
            'follow_up_date' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->sentence(),
            'user_id' => User::inRandomOrder()->first()->user_id,
        ];
    }
}