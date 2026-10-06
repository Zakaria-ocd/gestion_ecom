<?php

namespace Database\Seeders;

use App\Models\Choice;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

class ChoiceSeeder extends Seeder
{
    public function run(): void
    {
        $choiceValues = DemoCatalog::choiceValues();

        foreach (DemoCatalog::products() as $productData) {
            $product = Product::where('name', $productData['name'])->first();

            if (! $product) {
                throw new RuntimeException(
                    "Run ProductSeeder before ChoiceSeeder ({$productData['name']})."
                );
            }

            foreach (DemoCatalog::variants($productData) as $variant) {
                $choiceValue = DemoCatalog::findChoiceValue(
                    $choiceValues,
                    DemoCatalog::typeValueIds($variant),
                    DemoCatalog::price($productData, $variant)
                );

                if (! $choiceValue) {
                    throw new RuntimeException(
                        "Run ChoiceValueSeeder before ChoiceSeeder ({$productData['name']})."
                    );
                }

                Choice::firstOrCreate([
                    'product_id' => $product->id,
                    'choice_values_id' => $choiceValue->id,
                ]);
            }
        }
    }
}
