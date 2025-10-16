<?php

namespace Database\Factories;

use App\Models\Owner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OwnerFactory extends Factory
{
    protected $model = Owner::class;

    public function definition(): array
    {
        return [
            'own_name' => $this->faker->name(),
            'own_contactnum' => $this->faker->phoneNumber(),
            'own_location' => $this->faker->address(),
            'user_id' => User::inRandomOrder()->first()->id,
        ];
    }
}