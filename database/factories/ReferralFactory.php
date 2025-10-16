<?php

namespace Database\Factories;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'ref_date' => $this->faker->date(),
            'ref_description' => $this->faker->sentence(),
            'ref_by' => null,
            'ref_to' => null,
            'appoint_id' => null,
            'medical_history' => $this->faker->optional()->sentence(),
            'tests_conducted' => $this->faker->optional()->sentence(),
            'medications_given' => $this->faker->optional()->sentence(),
        ];
    }
}