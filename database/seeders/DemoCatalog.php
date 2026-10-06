<?php

namespace Database\Seeders;

use App\Models\Choice;
use App\Models\Product;
use App\Models\TypeValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoCatalog
{
    public static function categories(): array
    {
        return [
            'T-Shirts',
            'Hoodies',
            'Shoes',
            'Accessories',
        ];
    }

    public static function products(): array
    {
        return [
            [
                'name' => 'Classic Cotton T-Shirt',
                'category' => 'T-Shirts',
                'description' => 'A soft everyday cotton tee with a clean, easy-to-layer fit.',
                'rating' => 4.60,
                'base_price' => 22.00,
                'options' => [
                    'color' => ['Black', 'White', 'Blue', 'Red'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Everyday Pocket T-Shirt',
                'category' => 'T-Shirts',
                'description' => 'A comfortable cotton jersey shirt finished with a single chest pocket.',
                'rating' => 4.35,
                'base_price' => 30.00,
                'options' => [
                    'color' => ['Black', 'White', 'Blue'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Graphic Logo T-Shirt',
                'category' => 'T-Shirts',
                'description' => 'A mid-weight graphic tee designed for relaxed daily wear.',
                'rating' => 4.75,
                'base_price' => 38.00,
                'options' => [
                    'color' => ['Black', 'White', 'Red'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Classic Pullover Hoodie',
                'category' => 'Hoodies',
                'description' => 'A brushed-fleece pullover with a roomy hood and front pocket.',
                'rating' => 4.70,
                'base_price' => 54.00,
                'options' => [
                    'color' => ['Black', 'Gray', 'Navy'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Zip-Up Fleece Hoodie',
                'category' => 'Hoodies',
                'description' => 'A versatile full-zip fleece layer with ribbed cuffs and hem.',
                'rating' => 4.45,
                'base_price' => 66.00,
                'options' => [
                    'color' => ['Black', 'Gray', 'Navy'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Everyday Running Shoe',
                'category' => 'Shoes',
                'description' => 'A lightweight cushioned trainer for daily walks and easy runs.',
                'rating' => 4.55,
                'base_price' => 82.00,
                'options' => [
                    'color' => ['Black', 'White', 'Blue'],
                ],
            ],
            [
                'name' => 'Leather Court Sneaker',
                'category' => 'Shoes',
                'description' => 'A low-profile court sneaker with a smooth leather upper.',
                'rating' => 4.80,
                'base_price' => 96.00,
                'options' => [
                    'color' => ['Black', 'White', 'Brown'],
                ],
            ],
            [
                'name' => 'Canvas Low-Top Sneaker',
                'category' => 'Shoes',
                'description' => 'A durable canvas lace-up with a flexible rubber outsole.',
                'rating' => 4.25,
                'base_price' => 110.00,
                'options' => [
                    'color' => ['Black', 'White', 'Red'],
                ],
            ],
            [
                'name' => 'Everyday Leather Belt',
                'category' => 'Accessories',
                'description' => 'A classic leather belt with a brushed metal buckle.',
                'rating' => 4.40,
                'base_price' => 36.00,
                'options' => [
                    'color' => ['Black', 'Brown'],
                    'size' => ['S', 'M', 'L', 'XL'],
                ],
            ],
            [
                'name' => 'Minimal Face Watch',
                'category' => 'Accessories',
                'description' => 'A clean, understated watch with a simple interchangeable strap.',
                'rating' => 4.65,
                'base_price' => 128.00,
                'options' => [
                    'color' => ['Black', 'Silver', 'Gold'],
                ],
            ],
        ];
    }

    public static function variants(array $product): array
    {
        $variants = [[]];

        foreach ($product['options'] as $type => $values) {
            $expanded = [];

            foreach ($variants as $variant) {
                foreach ($values as $value) {
                    $expanded[] = $variant + [$type => $value];
                }
            }

            $variants = $expanded;
        }

        return $variants;
    }

    public static function price(array $product, array $variant): string
    {
        $price = (float) $product['base_price'];

        foreach ($product['options'] as $type => $values) {
            $index = array_search($variant[$type], $values, true);

            if ($index === false) {
                throw new RuntimeException("Unknown {$type} option in demo catalog.");
            }

            $price += $index * ($type === 'size' ? 1.50 : 0.25);
        }

        return number_format($price, 2, '.', '');
    }

    public static function typeValueIds(array $variant): array
    {
        $ids = [];

        foreach ($variant as $type => $value) {
            $typeValue = TypeValue::where('value', $value)
                ->whereHas('type', fn ($query) => $query->where('name', $type))
                ->first();

            if (! $typeValue) {
                throw new RuntimeException("Missing seeded {$type} value: {$value}.");
            }

            $ids[] = (int) $typeValue->id;
        }

        sort($ids);

        return $ids;
    }

    public static function findChoiceValue(
        Collection $choiceValues,
        array $typeValueIds,
        string $price
    ) {
        return $choiceValues->first(function ($choiceValue) use ($typeValueIds, $price) {
            return $choiceValue->type_value_ids === $typeValueIds
                && number_format((float) $choiceValue->price, 2, '.', '') === $price;
        });
    }

    public static function choiceValues(): Collection
    {
        return DB::table('choice_values')->get()->map(function ($choiceValue) {
            $choiceValue->type_value_ids = DB::table('type_value_choice_value')
                ->where('choice_value_id', $choiceValue->id)
                ->orderBy('type_value_id')
                ->pluck('type_value_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return $choiceValue;
        });
    }

    public static function choiceValuePrice(Choice $choice): float
    {
        $choiceValue = DB::table('choice_values')
            ->where('id', $choice->choice_values_id)
            ->first();

        if (! $choiceValue) {
            throw new RuntimeException("Missing choice value for choice {$choice->id}.");
        }

        return (float) $choiceValue->price;
    }

    public static function findChoice(array $item): Choice
    {
        $product = Product::where('name', $item['product'])->first();

        if (! $product) {
            throw new RuntimeException("Missing demo product: {$item['product']}.");
        }

        $choice = $product->choices()->get()->first(function ($choice) use ($item) {
            $rows = DB::table('type_value_choice_value')
                ->join(
                    'type_values',
                    'type_values.id',
                    '=',
                    'type_value_choice_value.type_value_id'
                )
                ->join('types', 'types.id', '=', 'type_values.type_id')
                ->where(
                    'type_value_choice_value.choice_value_id',
                    $choice->choice_values_id
                )
                ->get(['types.name as type_name', 'type_values.value']);
            $options = [];

            foreach ($rows as $row) {
                $options[$row->type_name] = $row->value;
            }

            ksort($options);
            $wanted = $item['options'];
            ksort($wanted);

            return $options === $wanted;
        });

        if (! $choice) {
            throw new RuntimeException(
                "Missing seeded choice for {$item['product']}."
            );
        }

        return $choice;
    }

    public static function orders(): array
    {
        return [
            [
                'notes' => 'Demo order 1',
                'delivery_status' => 'pending',
                'payment_status' => 'pending',
                'days_ago' => 2,
                'address' => '18 Market Street',
                'city' => 'Casablanca',
                'postal_code' => '20000',
                'phone' => '+212600000101',
                'items' => [
                    [
                        'product' => 'Classic Cotton T-Shirt',
                        'options' => ['color' => 'Black', 'size' => 'M'],
                        'quantity' => 2,
                    ],
                    [
                        'product' => 'Everyday Running Shoe',
                        'options' => ['color' => 'White'],
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'notes' => 'Demo order 2',
                'delivery_status' => 'delivered',
                'payment_status' => 'paid',
                'days_ago' => 14,
                'address' => '42 Garden Avenue',
                'city' => 'Rabat',
                'postal_code' => '10000',
                'phone' => '+212600000102',
                'items' => [
                    [
                        'product' => 'Classic Pullover Hoodie',
                        'options' => ['color' => 'Navy', 'size' => 'L'],
                        'quantity' => 1,
                    ],
                    [
                        'product' => 'Everyday Leather Belt',
                        'options' => ['color' => 'Brown', 'size' => 'M'],
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'notes' => 'Demo order 3',
                'delivery_status' => 'shipped',
                'payment_status' => 'pending',
                'days_ago' => 6,
                'address' => '7 Palm Road',
                'city' => 'Marrakesh',
                'postal_code' => '40000',
                'phone' => '+212600000103',
                'items' => [
                    [
                        'product' => 'Graphic Logo T-Shirt',
                        'options' => ['color' => 'Red', 'size' => 'XL'],
                        'quantity' => 1,
                    ],
                    [
                        'product' => 'Minimal Face Watch',
                        'options' => ['color' => 'Silver'],
                        'quantity' => 1,
                    ],
                ],
            ],
        ];
    }
}
