<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'buyer@example.com')->first();

        if (! $buyer) {
            throw new RuntimeException('Run UserSeeder before OrderSeeder.');
        }

        foreach (DemoCatalog::orders() as $orderData) {
            $total = 0;

            foreach ($orderData['items'] as $item) {
                $choice = DemoCatalog::findChoice($item);
                $total += DemoCatalog::choiceValuePrice($choice) * $item['quantity'];
            }

            DB::table('orders')->updateOrInsert(
                [
                    'user_id' => $buyer->id,
                    'notes' => $orderData['notes'],
                ],
                [
                    'total_amount' => number_format($total, 2, '.', ''),
                    'payment_method' => 'cash_on_delivery',
                    'payment_status' => $orderData['payment_status'],
                    'delivery_status' => $orderData['delivery_status'],
                    'address' => $orderData['address'],
                    'city' => $orderData['city'],
                    'postal_code' => $orderData['postal_code'],
                    'phone' => $orderData['phone'],
                    'created_at' => now()->subDays($orderData['days_ago']),
                    'updated_at' => now()->subDays($orderData['days_ago']),
                ]
            );

            if (! DB::table('orders')
                ->where('user_id', $buyer->id)
                ->where('notes', $orderData['notes'])
                ->exists()) {
                throw new RuntimeException("Unable to create {$orderData['notes']}.");
            }
        }
    }
}
