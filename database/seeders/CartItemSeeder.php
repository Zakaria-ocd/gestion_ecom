<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'buyer@example.com')->first();
        $cart = $buyer ? Cart::where('user_id', $buyer->id)->first() : null;

        if (! $cart) {
            throw new RuntimeException('Run CartSeeder before CartItemSeeder.');
        }

        $items = [
            [
                'product' => 'Classic Cotton T-Shirt',
                'options' => ['color' => 'Black', 'size' => 'M'],
                'quantity' => 1,
            ],
            [
                'product' => 'Everyday Running Shoe',
                'options' => ['color' => 'White'],
                'quantity' => 1,
            ],
            [
                'product' => 'Minimal Face Watch',
                'options' => ['color' => 'Silver'],
                'quantity' => 1,
            ],
        ];

        foreach ($items as $item) {
            $choice = DemoCatalog::findChoice($item);
            $price = DemoCatalog::choiceValuePrice($choice);

            DB::table('cart_items')->updateOrInsert(
                [
                    'cart_id' => $cart->id,
                    'product_id' => $choice->product_id,
                    'choice_value_id' => $choice->choice_values_id,
                ],
                [
                    'choice_id' => $choice->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
