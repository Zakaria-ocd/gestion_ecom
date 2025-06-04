<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $category = Category::create($request->all());
        return response()->json(['message' => 'Category created successfully', 'data' => $category]);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = Category::find($request->id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();
        return response()->json(['message' => 'Category updated successfully']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = Category::find($request->id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
