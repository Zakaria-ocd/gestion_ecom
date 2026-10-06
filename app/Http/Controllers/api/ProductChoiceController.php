<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Choice;
use App\Models\ChoiceValue;
use App\Models\Product;
use App\Models\TypeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductChoiceController extends Controller
{
    public function index(Product $product)
    {
        $choices = $product->choices()->with(['choiceValue.typeValues.type'])->get()
            ->map(function ($choice) {
                $typeValuePairs = [];
                foreach ($choice->choiceValue->typeValues->unique('id') as $typeValue) {
                    $typeValuePairs[] = [
                        'typeId' => $typeValue->type->id,
                        'typeName' => $typeValue->type->name,
                        'valueId' => $typeValue->id,
                        'value' => $typeValue->value,
                        'colorCode' => $typeValue->colorCode,
                    ];
                }

                return [
                    'id' => $choice->id,
                    'choice_value_id' => $choice->choice_values_id,
                    'typeValuePairs' => $typeValuePairs,
                    'price' => (float) $choice->choiceValue->price,
                    'quantity' => (int) $choice->choiceValue->quantity,
                ];
            });

        return response()->json(['data' => $choices]);
    }

    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'typeValuePairs' => 'required|array',
            'typeValuePairs.*.typeId' => 'required|exists:types,id',
            'typeValuePairs.*.valueId' => 'required|exists:type_values,id',
            'typeValuePairs.*.colorCode' => 'nullable|string|max:7',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);
        $validator->after(function ($validator) use ($request) {
            $pairs = $request->input('typeValuePairs', []);
            if (! is_array($pairs)) {
                return;
            }
            foreach ($pairs as $index => $pair) {
                if (! TypeValue::whereKey($pair['valueId'] ?? null)
                    ->where('type_id', $pair['typeId'] ?? null)
                    ->exists()) {
                    $validator->errors()->add(
                        "typeValuePairs.{$index}.valueId",
                        'The selected value must belong to the selected type.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = DB::transaction(function () use ($request, $product) {
            $choiceValue = ChoiceValue::create([
                'price' => $request->price,
                'quantity' => $request->quantity,
            ]);

            foreach ($request->typeValuePairs as $pair) {
                $typeValue = TypeValue::findOrFail($pair['valueId']);
                $choiceValue->typeValues()->attach($typeValue->id);
            }

            $choice = Choice::create([
                'product_id' => $product->id,
                'choice_values_id' => $choiceValue->id,
            ]);

            return [
                'choice' => $choice,
                'choiceValue' => $choiceValue,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $result['choice']->id,
                'choice_value_id' => $result['choice']->choice_values_id,
                'typeValuePairs' => $request->typeValuePairs,
                'price' => (float) $result['choiceValue']->price,
                'quantity' => (int) $result['choiceValue']->quantity,
            ],
            'message' => 'Product choice created successfully',
        ], 201);
    }

    public function update(Request $request, Product $product, Choice $choice)
    {
        if ($choice->product_id !== $product->id) {
            return response()->json(['message' => 'Choice not found for this product'], 404);
        }

        $validator = Validator::make($request->all(), [
            'typeValuePairs' => 'required|array',
            'typeValuePairs.*.typeId' => 'required|exists:types,id',
            'typeValuePairs.*.valueId' => 'required|exists:type_values,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);
        $validator->after(function ($validator) use ($request) {
            $pairs = $request->input('typeValuePairs', []);
            if (! is_array($pairs)) {
                return;
            }
            foreach ($pairs as $index => $pair) {
                if (! TypeValue::whereKey($pair['valueId'] ?? null)
                    ->where('type_id', $pair['typeId'] ?? null)
                    ->exists()) {
                    $validator->errors()->add(
                        "typeValuePairs.{$index}.valueId",
                        'The selected value must belong to the selected type.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = DB::transaction(function () use ($request, $choice) {
            $choiceValue = $choice->choiceValue;

            $choiceValue->update([
                'price' => $request->price,
                'quantity' => $request->quantity,
            ]);

            $typeValueIds = array_column($request->typeValuePairs, 'valueId');
            $choiceValue->typeValues()->sync($typeValueIds);

            return $choiceValue;
        });

        return response()->json([
            'data' => [
                'id' => $choice->id,
                'choice_value_id' => $choice->choice_values_id,
                'typeValuePairs' => $request->typeValuePairs,
                'price' => (float) $result->price,
                'quantity' => (int) $result->quantity,
            ],
            'message' => 'Product choice updated successfully',
        ]);
    }

    public function destroy(Product $product, Choice $choice)
    {
        if ($choice->product_id !== $product->id) {
            return response()->json(['message' => 'Choice not found for this product'], 404);
        }

        DB::transaction(function () use ($choice) {
            $choiceValueId = $choice->choice_values_id;
            $choice->delete();
            ChoiceValue::destroy($choiceValueId);
        });

        return response()->json(['message' => 'Product choice deleted successfully']);
    }
}
