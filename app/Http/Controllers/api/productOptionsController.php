<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\ProductOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductOptionsController extends Controller
{
    public function index()
    {
        // $options = DB::table('option_values')
        // ->join('options', 'option_values.option_id', '=', 'options.id')
        // ->select('option_values.id AS option_values_id', 'option_values.value',
        //     'options.id', 'options.name')
        // ->get();
        // // $options = DB::table('products')
        // // ->join('product_options', 'products.id', '=', 'product_options.product_id')
        // // ->join('option_values', 'product_options.option_value_id', '=', 'option_values.id')
        // // ->leftJoin('options', 'option_values.option_id', '=', 'options.id')
        // // ->select('product_options.id', 'product_options.product_id', 'option_values.value', 'options.name')
        // // ->get();
        // return response()->json($options,200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'product_id'                        => 'required|exists:products,id',
            'options'                           => 'required|array',
            'options.*.values'                  => 'required|array',
            'options.*.values.*.option_value_id'=> 'required|exists:option_values,id',
            'options.*.values.*.price'          => 'required|numeric|min:0',
            'options.*.values.*.quantity'       => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->options as $option) {
                foreach ($option['values'] as $value) {
                    ProductOption::create([
                        'product_id'      => $request->product_id,
                        'option_value_id' => $value['option_value_id'],
                        'price'           => $value['price'],
                        'quantity'        => $value['quantity'],
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' => true], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
               'error' => $e->getMessage()
            ], 500);
        }
    }
}