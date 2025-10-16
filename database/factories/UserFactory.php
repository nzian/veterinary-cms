<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Arr;

class UserFactory extends Factory
{
    protected $model = User::class;
      /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'user_name' => fake()->name(),
            'user_email' => fake()->unique()->safeEmail(),
            'user_password' => static::$password ??= Hash::make('password'),
            'user_contactNum' =>  fake()->numberBetween(10000000000, 99999999999),
            'user_licenseNum' =>  fake()->numberBetween(1000000000, 9999999999),
            'user_role' => Arr::random(['superadmin', 'veterinarian', 'receptionist']),
            'branch_id' => Branch::inRandomOrder()->first()->id,
            'last_login_at' => fake()->dateTimeBetween('-1 months', 'now'),
        ];
    }
}