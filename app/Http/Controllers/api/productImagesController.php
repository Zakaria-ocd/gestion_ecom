<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class productImagesController extends Controller
{
    public function productImages(Request $request)
    {
        $images = ProductImage::where('product_id', $request->productId)->pluck('image_url');

        return response()->json($images);
    }
    
    public function show($filename)
    {
        $isExists = !Storage::exists("products/{$filename}");
        if ($isExists) {
            return response()->json([
                'error' => 'Image not found'
            ], 404);
        }

        try {
            $file = Storage::get("products/{$filename}");
            $mimeType = Storage::mimeType("products/{$filename}");
            
            return response($file, 200)
                ->header('Content-Type', $mimeType);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Image not found'
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'images' => 'required|array|max:10',
            'images.*' => [
                'image',
                'mimes:jpeg,jpg,avif,png,webp',
                'max:5120'
            ],
        ]);

        try {
            $uploadedImages = [];
            
            foreach ($request->file('images') as $image) {
                // Store in private directory instead of public
                $path = $image->store('products');
                $filename = basename($path);
                
                $imageRecord = ProductImage::create([
                    'product_id' => $validated['product_id'],
                    'image_url' => $filename,
                ]);

                $uploadedImages[] = [
                    'id' => $imageRecord->id,
                    'url' => url('/api/image/' . $filename),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Add the method to retrieve product image
    public function getProductImage(Request $request)
    {
        $filename = DB::table('products')
            ->select("product_images.image_url")
            ->join('product_images', 'product_images.product_id', '=', 'products.id')
            ->where("products.id", '=', $request->product_id)
            ->first();
            
        if (!$filename) {
            return response()->json([
                'error' => 'Image not found'
            ], 404);
        }
        
        $filename = $filename->image_url;
        if (Storage::exists("products/{$filename}")) {
            $file = Storage::get("products/{$filename}");
            $mimeType = Storage::mimeType("products/{$filename}");
            return response($file, 200)->header('Content-Type', $mimeType);
        }
        
        return response()->json([
            'error' => 'Image not found'
        ], 404);
    }
}
