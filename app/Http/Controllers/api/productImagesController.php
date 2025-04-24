<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class productImagesController extends Controller
{
    public function show(Request $request)
    {
        $filename = ProductImage::find($request->imageId)?->image_url;

        if (Storage::exists("products/{$filename}")) {
            $file = Storage::get("products/{$filename}");
            $mimeType = Storage::mimeType("products/{$filename}");

            return response($file, 200)->header('Content-Type', $mimeType);
        }
    }
    public function store(Request $request)
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
}
