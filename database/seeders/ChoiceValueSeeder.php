<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChoiceValueSeeder extends Seeder
{
    public function run(): void
    {
        $choiceValues = DemoCatalog::choiceValues();

        foreach (DemoCatalog::products() as $product) {
            foreach (DemoCatalog::variants($product) as $variant) {
                $typeValueIds = DemoCatalog::typeValueIds($variant);
                $price = DemoCatalog::price($product, $variant);
                $choiceValue = DemoCatalog::findChoiceValue(
                    $choiceValues,
                    $typeValueIds,
                    $price
                );

                if (! $choiceValue) {
                    $now = now();
                    $choiceValueId = DB::table('choice_values')->insertGetId([
                        'price' => $price,
                        'quantity' => 12,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $choiceValue = (object) [
                        'id' => $choiceValueId,
                        'price' => $price,
                        'quantity' => 12,
                        'type_value_ids' => $typeValueIds,
                    ];
                    $choiceValues->push($choiceValue);
                } else {
                    DB::table('choice_values')
                        ->where('id', $choiceValue->id)
                        ->update(['quantity' => 12, 'updated_at' => now()]);
                    $choiceValue->quantity = 12;
                }

                foreach ($typeValueIds as $typeValueId) {
                    DB::table('type_value_choice_value')->updateOrInsert(
                        [
                            'choice_value_id' => $choiceValue->id,
                            'type_value_id' => $typeValueId,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                if (empty($choiceValue->type_value_ids)) {
                    throw new RuntimeException('A demo choice value has no option values.');
                }
            }
        }
    }
}
