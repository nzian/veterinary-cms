<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Testing\Fakes\Fake;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
                'appoint_time' => $this->faker->time('H:i:s'),
                'appoint_status' => $this->faker->randomElement(['pending','arrived','completed','rescheduled','cancelled']),
                'appoint_date' => $this->faker->date(),
                'appoint_description' => $this->faker->optional()->sentence(),
                'appoint_type' => $this->faker->randomElement(['Walk-in','Referral','Follow-up']),
                'pet_id' => Pet::inRandomOrder()->first()->pet_id,
                'ref_id' => Referral::inRandomOrder()->first()->ref_id,
                'user_id' => User::inRandomOrder()->first()->user_id,
        ];
    }
}