<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::where('email', 'seller@example.com')->first();

        if (! $seller) {
            throw new RuntimeException('Run UserSeeder before ProductSeeder.');
        }

        foreach (DemoCatalog::products() as $product) {
            $category = Category::where('name', $product['category'])->first();

            if (! $category) {
                throw new RuntimeException(
                    "Run CategorySeeder before ProductSeeder ({$product['category']})."
                );
            }

            Product::updateOrCreate(
                ['name' => $product['name']],
                [
                    'description' => $product['description'],
                    'category_id' => $category->id,
                    'seller_id' => $seller->id,
                    'rating' => $product['rating'],
                ]
            );
        }
    }
}
