<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'buyer@example.com')->first();

        if (! $buyer) {
            throw new RuntimeException('Run UserSeeder before OrderItemSeeder.');
        }

        foreach (DemoCatalog::orders() as $orderData) {
            $order = DB::table('orders')
                ->where('user_id', $buyer->id)
                ->where('notes', $orderData['notes'])
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    "Run OrderSeeder before OrderItemSeeder ({$orderData['notes']})."
                );
            }

            foreach ($orderData['items'] as $item) {
                $choice = DemoCatalog::findChoice($item);
                $price = DemoCatalog::choiceValuePrice($choice);

                DB::table('order_items')->updateOrInsert(
                    [
                        'order_id' => $order->id,
                        'product_id' => $choice->product_id,
                        'choice_value_id' => $choice->choice_values_id,
                    ],
                    [
                        'choice_id' => $choice->id,
                        'quantity' => $item['quantity'],
                        'price' => $price,
                        'total' => number_format($price * $item['quantity'], 2, '.', ''),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
