<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class categoriesController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json(['data' => $categories]);
    }

    public function show(Request $request)
    {
        $category = Category::find($request->id);
        return response()->json(['data' => $category]);
    }
    
    /**
     * Get all products in a specific category
     */
    public function products(Request $request)
    {
        $category = Category::find($request->id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
        
        $products = Product::where('category_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json([
            'category' => $category,
            'products' => $products
        ]);
    }
}
