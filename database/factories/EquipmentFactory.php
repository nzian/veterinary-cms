<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'equipment_name' => $this->faker->word(),
            'equipment_description' => $this->faker->sentence(),
            'equipment_quantity' => $this->faker->numberBetween(0,100),
            'equipment_category' => $this->faker->word(),
            'equipment_image' => null,
            'branch_id' => null,
        ];
    }
}