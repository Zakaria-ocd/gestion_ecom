<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Type;
use App\Models\TypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_catalog_has_no_numeric_sizes_and_size_values_are_not_colors(): void
    {
        $this->seed();

        $sizeType = Type::where('name', 'size')->firstOrFail();
        $sizeValues = TypeValue::where('type_id', $sizeType->id)->get();

        $this->assertFalse(
            $sizeValues->contains(fn (TypeValue $value) => preg_match('/^\d+$/D', trim($value->value)))
        );
        $this->assertFalse($sizeValues->contains(fn (TypeValue $value) => $value->colorCode !== ''));

        $availableChoices = $this->getJson('/api/available-choices')
            ->assertOk()
            ->json();
        $availableSizeValues = collect($availableChoices['availableValues'][$sizeType->id]);

        $this->assertFalse(
            $availableSizeValues->contains(
                fn (array $value) => preg_match('/^\d+(?:[.,]\d+)?$/D', trim($value['value']))
            )
        );

        $shoeProducts = Product::whereHas('category', fn ($query) => $query->where('name', 'Shoes'))
            ->with('choices.choiceValue.typeValues.type')
            ->get();

        $this->assertNotEmpty($shoeProducts);

        foreach ($shoeProducts as $product) {
            foreach ($product->choices as $choice) {
                foreach ($choice->choiceValue->typeValues as $typeValue) {
                    $this->assertSame('color', strtolower($typeValue->type->name));
                }
            }
        }

        $this->getJson("/api/products/{$shoeProducts->first()->id}/choices")
            ->assertOk()
            ->assertJsonMissing(['typeName' => 'size']);
    }
}
