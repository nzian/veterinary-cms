<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'prod_name' => $this->faker->word(),
            'prod_description' => $this->faker->sentence(),
            'prod_price' => $this->faker->randomFloat(2, 5, 2000),
            'prod_category' => $this->faker->randomElement(['Product','Service','Vaccine','Supply']),
            'prod_stocks' => $this->faker->numberBetween(0, 500),
            'prod_reorderlevel' => $this->faker->numberBetween(0, 50),
            'prod_image' => null,
            'prod_damaged' => 0,
            'prod_pullout' => 0,
            'prod_expiry' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
            'prod_min_stock' => $this->faker->numberBetween(10,100),
            'prod_reorderlevel' => $this->faker->numberBetween(5,20),
            'branch_id' => Branch::inRandomOrder()->first()->branch_id,
            'ord_id' => Order::inRandomOrder()->first()?->ord_id ?? NULL
        ];
    }
}