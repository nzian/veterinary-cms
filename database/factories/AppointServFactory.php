<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointServ;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointServFactory extends Factory
{
    protected $model = AppointServ::class;

    public function definition(): array
    {
        return [
            'appoint_id' => Appointment::factory(),
            'serv_id' => Service::factory(),
            'prod_id' => Product::factory(),
            'vet_user_id' => User::factory(),
            'vacc_next_dose' => $this->faker->optional()->date(),
            'vacc_batch_no' => $this->faker->lexify('????-####'),
            'vacc_notes' => $this->faker->optional()->sentence(),
        ];
    }
}