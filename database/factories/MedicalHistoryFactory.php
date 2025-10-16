<?php

namespace Database\Factories;

use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalHistoryFactory extends Factory
{
    protected $model = MedicalHistory::class;

    public function definition(): array
    {
        return [
            'pet_id' => null,
            'visit_date' => $this->faker->date(),
            'diagnosis' => $this->faker->sentence(),
            'treatment' => $this->faker->sentence(),
            'medication' => $this->faker->sentence(),
            'veterinarian_name' => $this->faker->name(),
            'follow_up_date' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->sentence(),
            'differential_diagnosis' => $this->faker->optional()->sentence(),
            'user_id' => null,
            'branch_id' => null,
        ];
    }
}