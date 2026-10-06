<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'buyer@example.com')->first();

        if (! $buyer) {
            throw new RuntimeException('Run UserSeeder before CartSeeder.');
        }

        Cart::firstOrCreate(['user_id' => $buyer->id]);
    }
}
