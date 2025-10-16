<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'ord_quantity' => $this->faker->numberBetween(1,10),
            'ord_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'user_id' => User::inRandomOrder()->first()->id,
            'prod_id' => Product::inRandomOrder()->first()->id,
            'ord_total' => $this->faker->randomFloat(2, 1, 5000),
        ];
    }
}