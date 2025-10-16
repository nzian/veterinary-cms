<?php

namespace Database\Factories;

use App\Models\ServiceProduct;
use App\Models\Service;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceProductFactory extends Factory
{
    protected $model = ServiceProduct::class;

    public function definition(): array
    {
        return [
            'serv_id' => Service::inRandomOrder()->first()->serv_id,
            'prod_id' => Product::inRandomOrder()->first()->prod_id,
            'quantity_used' => $this->faker->randomFloat(2, 1, 10),
            'is_billable' => $this->faker->boolean(80),
        ];
    }
}