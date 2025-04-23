<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function getImage($filename)
    {
        if (!Storage::exists("products/{$filename}")) {
            abort(404, 'Image not found');
        }

        $file = Storage::get("products/{$filename}");
        $mimeType = Storage::mimeType("products/{$filename}");

        return response($file, 200)->header('Content-Type', $mimeType);
    }
    public function uploadImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_id' => 'required|exists:products,id',
        ]);

        $product_id = $request->input('product_id');
        $imageInfos = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('products');
            $filename = basename($path);

            $imageInfo = [
                'product_id' => $product_id,
                'image_url' => $filename,
            ];

            DB::table('product_images')->insert($imageInfo);

            $imageInfos[] = [
                'url' => $filename,
            ];
        }

        return response()->json(['images' => $imageInfos], 200);
    }
    public function getCategories()
    {
        $categories = Category::select('id', 'name')->get();
        return response()->json($categories);
    }
}
