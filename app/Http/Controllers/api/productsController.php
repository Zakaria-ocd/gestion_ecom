<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class productsController extends Controller
{
    public function index(){
        $products=Product::all();
        return response()->json($products,200);
    }
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'seller_id' => 'required|exists:users,id',
        ]);
    
        $product = Product::create($request->only([
            'name', 'description', 'price', 'category_id', 'seller_id',
        ]));

        return response()->json(['product' => $product], 201);
    }
    public function getCategories()
    {
        $categories = Category::select('id', 'name')->get();
        return response()->json($categories);
    }
}
