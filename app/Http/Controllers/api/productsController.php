<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function getProducts(){
        
        return response()->json(
            DB::table('products')
            ->select('products.*')
            ->join('choices',"choices.product_id","=","products.id")
            ->distinct()
            ->get()
        );
    }
    public function getProductDefaultPrice(Request $request){
       
        return response()->json(
            DB::table('products')->select('choice_values.price')
            ->join('choices',"choices.product_id","=","products.id")
            ->join("choice_values","choice_values.id","=",'choices.choice_values_id')
            ->where("products.id",'=',$request->product_id)
            ->limit(1)
            ->first()
        );
    }
    public function getProductImage(Request $request){

        $filename =DB::table('products')
        ->select("product_images.image_url")
        ->join('product_images','product_images.product_id','=','products.id')
        ->where("products.id",'=',$request->product_id)
        ->first();
        $filename=$filename->image_url;
        if (Storage::exists("products/{$filename}")) {
            $file = Storage::get("products/{$filename}");
            $mimeType = Storage::mimeType("products/{$filename}");
            return response($file, 200)->header('Content-Type', $mimeType);
        }
    
    }
    public function getProductOptions(Request $request){
        return response()->json(
            DB::table('products')
            ->select("type_values.type_id",'types.name',
            "type_values.id as type_value_id","type_values.value",'type_values.colorCode')
            ->join('choices',"choices.product_id","=","products.id")
            ->join("choice_values","choice_values.id","=",'choices.choice_values_id')
            ->join('type_value_choice_value','type_value_choice_value.choice_value_id',"=",
            "choice_values.id")
            ->join("type_values",'type_values.id',"=","type_value_choice_value.type_value_id")
            ->join("types",'types.id',"=","type_values.type_id")
            ->where("products.id",'=',$request->product_id)
            ->get()
        );
    }
}