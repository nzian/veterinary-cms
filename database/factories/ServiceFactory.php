<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'serv_name' => $this->faker->words(3, true),
            'serv_description' => $this->faker->sentence(),
            'serv_price' => $this->faker->randomFloat(2, 50, 5000),
            'serv_type' => $this->faker->randomElement(['Consultation','Procedure','Vaccination']),
            'branch_id' => Branch::inRandomOrder()->first()->branch_id,
        ];
    }
}