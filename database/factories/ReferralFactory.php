<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\User;
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
            'ref_by' => User::whereIn('user_role', ['receptionist','veterinarian'])->inRandomOrder()->first()->id,
            'ref_to' => Branch::inRandomOrder()->first()->branch_id,
            'medical_history' => $this->faker->optional()->sentence(),
            'tests_conducted' => $this->faker->optional()->sentence(),
            'medications_given' => $this->faker->optional()->sentence(),
        ];
    }
}