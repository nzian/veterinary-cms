<?php

namespace Database\Factories;

use App\Models\Owner;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'pet_weight' => $this->faker->randomFloat(1, 0.5, 80),
            'pet_species' => $this->faker->randomElement(['Dog','Cat','Bird','Other']),
            'pet_breed' => $this->faker->word(),
            'pet_birthdate' => $this->faker->date(),
            'pet_age' => $this->faker->numberBetween(0,20),
            'pet_name' => $this->faker->firstName(),
            'pet_photo' => null,
            'pet_gender' => $this->faker->randomElement(['Male','Female']),
            'pet_registration' => $this->faker->date(),
            'pet_temperature' => $this->faker->randomFloat(1, 36, 40),
            'own_id' => Owner::inRandomOrder()->first()->own_id,
            'user_id' => User::inRandomOrder()->first()->user_id,
        ];
    }
}