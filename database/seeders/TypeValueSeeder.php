<?php

namespace Database\Seeders;

use App\Models\Type;
use App\Models\TypeValue;
use Illuminate\Database\Seeder;

class TypeValueSeeder extends Seeder
{
    private const COLOR_CODES = [
        'Black' => '#111827',
        'White' => '#F9FAFB',
        'Blue' => '#2563EB',
        'Red' => '#DC2626',
        'Gray' => '#6B7280',
        'Navy' => '#1E3A8A',
        'Brown' => '#78350F',
        'Silver' => '#9CA3AF',
        'Gold' => '#D6B25E',
    ];

    public function run(): void
    {
        foreach (DemoCatalog::products() as $product) {
            foreach ($product['options'] as $typeName => $values) {
                $type = Type::where('name', $typeName)->firstOrFail();

                foreach ($values as $value) {
                    TypeValue::updateOrCreate(
                        [
                            'type_id' => $type->id,
                            'value' => $value,
                        ],
                        [
                            'colorCode' => $typeName === 'color'
                                ? (self::COLOR_CODES[$value] ?? '#000000')
                                : '',
                        ]
                    );
                }
            }
        }
    }
}
