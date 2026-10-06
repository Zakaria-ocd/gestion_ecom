<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class productsController extends Controller
{
    public function index()
    {
        $products = Product::all();

        return response()->json(['data' => $products]);
    }

    public function show(Product $product)
    {
        return response()->json(['data' => $product]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'seller_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());

        return response()->json(['data' => $product, 'message' => 'Product created successfully'], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->all());

        return response()->json(['data' => $product, 'message' => 'Product updated successfully']);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function getProducts()
    {

        return response()->json(
            DB::table('products')
                ->select('products.*')
                ->join('choices', 'choices.product_id', '=', 'products.id')
                ->distinct()
                ->get()
        );
    }

    public function getProductDefaultPrice(Request $request)
    {

        return response()->json(
            DB::table('products')->select('choice_values.price')
                ->join('choices', 'choices.product_id', '=', 'products.id')
                ->join('choice_values', 'choice_values.id', '=', 'choices.choice_values_id')
                ->where('products.id', '=', $request->product_id)
                ->limit(1)
                ->first()
        );
    }

    public function getProductImage(Request $request)
    {
        $filename = DB::table('products')
            ->select('product_images.image_url')
            ->join('product_images', 'product_images.product_id', '=', 'products.id')
            ->where('products.id', '=', $request->product_id)
            ->first();

        if (! $filename) {
            return response()->json([
                'error' => 'Image not found',
            ], 404);
        }

        $filename = $filename->image_url;
        if (Storage::exists("products/{$filename}")) {
            $file = Storage::get("products/{$filename}");
            $mimeType = Storage::mimeType("products/{$filename}");

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return response()->json([
            'error' => 'Image not found',
        ], 404);
    }

    public function getProductOptions(Request $request)
    {
        return response()->json(
            DB::table('products')
                ->select('type_values.type_id', 'types.name',
                    'type_values.id as type_value_id', 'type_values.value', 'type_values.colorCode', 'choices.id as choice_id')
                ->join('choices', 'choices.product_id', '=', 'products.id')
                ->join('choice_values', 'choice_values.id', '=', 'choices.choice_values_id')
                ->join('type_value_choice_value', 'type_value_choice_value.choice_value_id', '=',
                    'choice_values.id')
                ->join('type_values', 'type_values.id', '=', 'type_value_choice_value.type_value_id')
                ->join('types', 'types.id', '=', 'type_values.type_id')
                ->where('products.id', '=', $request->product_id)
                ->get()
        );
    }

    public function getProductChoices(Request $request)
    {
        return response()->json(
            DB::table('products')
                ->select('type_value_choice_value.choice_value_id',
                    'type_value_choice_value.type_value_id', 'type_values.type_id', 'choices.id as choice_id', 'choice_values.quantity', 'choice_values.price')
                ->join('choices', 'choices.product_id', '=', 'products.id')
                ->join('choice_values', 'choice_values.id', '=', 'choices.choice_values_id')
                ->join('type_value_choice_value', 'type_value_choice_value.choice_value_id', '=',
                    'choice_values.id')
                ->join('type_values', 'type_values.id', '=', 'type_value_choice_value.type_value_id')
                ->where('products.id', '=', $request->product_id)
                ->get()
        );
    }

    public function filterProducts(Request $request)
    {

        $query = DB::table('products')->select('products.*');

        $query->leftJoin('choices', 'choices.product_id', '=', 'products.id')
            ->leftJoin('choice_values', 'choice_values.id', '=', 'choices.choice_values_id');

        if ($request->has('colors') || $request->has('sizes')) {
            $query->leftJoin('type_value_choice_value', 'type_value_choice_value.choice_value_id', '=', 'choice_values.id')
                ->leftJoin('type_values', 'type_values.id', '=', 'type_value_choice_value.type_value_id')
                ->leftJoin('types', 'types.id', '=', 'type_values.type_id');
        }

        if ($request->has('categories') && ! empty($request->categories)) {
            $categories = explode(',', $request->categories);
            $query->whereIn('products.category_id', $categories);
        }

        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $query->where('choice_values.price', '>=', $request->min_price);
        }

        if ($request->has('min_rating') && is_numeric($request->min_rating)) {
            $query->where('products.rating', '>=', $request->min_rating);
        }

        if ($request->has('colors') && ! empty($request->colors)) {
            $colorValues = explode(',', $request->colors);
            $colorCount = count($colorValues);

            $query->whereIn('products.id', function ($subquery) use ($colorValues) {
                $subquery->select('products.id')
                    ->from('products')
                    ->join('choices', 'choices.product_id', '=', 'products.id')
                    ->join('choice_values', 'choice_values.id', '=', 'choices.choice_values_id')
                    ->join('type_value_choice_value', 'type_value_choice_value.choice_value_id', '=', 'choice_values.id')
                    ->join('type_values', 'type_values.id', '=', 'type_value_choice_value.type_value_id')
                    ->join('types', 'types.id', '=', 'type_values.type_id')
                    ->where('types.name', 'color')
                    ->whereIn('type_values.id', $colorValues);
            });
        }

        if ($request->has('sizes') && ! empty($request->sizes)) {
            $sizeValues = explode(',', $request->sizes);
            $sizeCount = count($sizeValues);

            $query->whereIn('products.id', function ($subquery) use ($sizeValues) {
                $subquery->select('products.id')
                    ->from('products')
                    ->join('choices', 'choices.product_id', '=', 'products.id')
                    ->join('choice_values', 'choice_values.id', '=', 'choices.choice_values_id')
                    ->join('type_value_choice_value', 'type_value_choice_value.choice_value_id', '=', 'choice_values.id')
                    ->join('type_values', 'type_values.id', '=', 'type_value_choice_value.type_value_id')
                    ->join('types', 'types.id', '=', 'type_values.type_id')
                    ->where('types.name', 'size')
                    ->whereIn('type_values.id', $sizeValues);
            });
        }

        $query->distinct();

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('choice_values.price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('choice_values.price', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('products.rating', 'desc');
                    break;
                case 'popular':
                    $query->orderByDesc(
                        DB::table('order_items')
                            ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                            ->whereColumn('order_items.product_id', 'products.id')
                    );
                    break;
                case 'newest':
                default:
                    $query->orderBy('products.created_at', 'desc');
                    break;
            }
        } else {

            $query->orderBy('products.id', 'desc');
        }

        $perPage = $request->has('per_page') ? intval($request->per_page) : 24;
        $page = $request->has('page') ? intval($request->page) : 1;

        $countQuery = clone $query;

        $total = $countQuery->count('products.id');

        $products = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'data' => $products,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }
}
