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
            'appoint_id' => Appointment::inRandomOrder()->first()->appoint_id,
            'serv_id' => Service::inRandomOrder()->first()->serv_id,
            'prod_id' => Product::inRandomOrder()->first()->prod_id,
            'vet_user_id' => User::whereIn('user_role',['veterinarian'])->inRandomOrder()->first()->user_id,
            'vacc_next_dose' => $this->faker->optional()->date(),
            'vacc_batch_no' => $this->faker->lexify('????-####'),
            'vacc_notes' => $this->faker->optional()->sentence(),
        ];
    }
}