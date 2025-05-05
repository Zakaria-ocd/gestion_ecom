<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class productsController extends Controller
{
    public function index()
    {
        return response()->json(Product::all(), 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'seller_id' => 'required|exists:users,id',
            'price' => 'required_without:options|numeric|min:0.01',
            'quantity' => 'required_without:options|integer|min:1',
            'options' => 'nullable|array',
            'options.*.option_id' => 'required_with:options|exists:options,id',
            'options.*.values' => 'required_with:options|array|min:1',
            'options.*.values.*.option_value_id' => 'required_with:options|exists:option_values,id',
            'options.*.values.*.price' => 'required_with:options|numeric|min:0.01',
            'options.*.values.*.quantity' => 'required_with:options|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'seller_id' => $request->seller_id,

                'price' => $request->has('options') ? null : $request->price,
                'quantity' => $request->has('options') ? null : $request->quantity,
            ]);

            if ($request->has('options')) {
                foreach ($request->options as $option) {
                    foreach ($option['values'] as $value) {
                        ProductOption::create([
                            'product_id' => $product->id,
                            'option_value_id' => $value['option_value_id'],
                            'price' => $value['price'],
                            'quantity' => $value['quantity'],
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json($product->id, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getCategories()
    {
        return response()->json(\App\Models\Category::select('id','name')->get());
    }
}