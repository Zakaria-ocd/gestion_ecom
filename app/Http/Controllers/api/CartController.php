<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ChoiceValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = $request->user()->cart;
        $items = $cart
            ? $cart->items()->with(['product', 'choiceValue.typeValues.type'])->get()
            : collect();
        $formattedItems = $items->map(fn (CartItem $item) => $this->formatItem($item));

        return response()->json([
            'status' => 'success',
            'cart_items' => $formattedItems,
            'total_price' => $formattedItems->sum(fn (array $item) => $item['price'] * $item['quantity']),
        ]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'choice_value_id' => ['required', 'exists:choice_values,id'],
        ]);

        $choiceValue = ChoiceValue::whereKey($validated['choice_value_id'])
            ->whereHas('choices', fn ($query) => $query->where('product_id', $validated['product_id']))
            ->first();

        if (! $choiceValue) {
            throw ValidationException::withMessages([
                'choice_value_id' => ['The selected option is not available for this product.'],
            ]);
        }
        if ($validated['quantity'] > $choiceValue->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The selected option does not have enough stock.'],
            ]);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $validated['product_id'],
            'choice_value_id' => $choiceValue->id,
        ]);
        $newQuantity = ($item->exists ? $item->quantity : 0) + $validated['quantity'];
        if ($newQuantity > $choiceValue->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The selected option does not have enough stock.'],
            ]);
        }
        $item->quantity = $newQuantity;
        $item->price = $choiceValue->price;
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart',
            'cart_item' => $this->formatItem($item->load(['product', 'choiceValue.typeValues.type'])),
        ], 201);
    }

    public function updateCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = CartItem::whereKey($validated['cart_item_id'])
            ->where('cart_id', $request->user()->cart?->id)
            ->with('choiceValue')
            ->first();

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Item not found in cart'], 404);
        }
        if ($item->choiceValue && $validated['quantity'] > $item->choiceValue->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The selected option does not have enough stock.'],
            ]);
        }

        $item->quantity = $validated['quantity'];
        $item->save();

        $items = $request->user()->cart->items()
            ->with(['product', 'choiceValue.typeValues.type'])
            ->get()
            ->map(fn (CartItem $cartItem) => $this->formatItem($cartItem));

        return response()->json([
            'status' => 'success',
            'message' => 'Cart updated',
            'cart_item' => $this->formatItem($item->load(['product', 'choiceValue.typeValues.type'])),
            'cart_items' => $items,
            'total_price' => $this->total($items),
        ]);
    }

    public function removeFromCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => ['required', 'integer'],
            'choice_value_id' => ['nullable', 'integer'],
        ]);

        $query = CartItem::whereKey($validated['cart_item_id'])
            ->where('cart_id', $request->user()->cart?->id);
        if (isset($validated['choice_value_id'])) {
            $query->where('choice_value_id', $validated['choice_value_id']);
        }

        if (! $query->delete()) {
            return response()->json(['status' => 'error', 'message' => 'Item not found in cart'], 404);
        }

        $items = $request->user()->cart->items()
            ->with(['product', 'choiceValue.typeValues.type'])
            ->get()
            ->map(fn (CartItem $item) => $this->formatItem($item));

        return response()->json([
            'status' => 'success',
            'message' => 'Item removed from cart',
            'cart_items' => $items,
            'total_price' => $this->total($items),
        ]);
    }

    public function mergeCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.choice_value_id' => ['required', 'exists:choice_values,id'],
        ]);

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        DB::transaction(function () use ($validated, $cart) {
            foreach ($validated['items'] as $entry) {
                $choiceValue = ChoiceValue::whereKey($entry['choice_value_id'])
                    ->whereHas('choices', fn ($query) => $query->where('product_id', $entry['product_id']))
                    ->first();
                if (! $choiceValue) {
                    throw ValidationException::withMessages([
                        'items' => ['A selected option is not available for its product.'],
                    ]);
                }

                $item = CartItem::firstOrNew([
                    'cart_id' => $cart->id,
                    'product_id' => $entry['product_id'],
                    'choice_value_id' => $choiceValue->id,
                ]);
                $quantity = ($item->exists ? $item->quantity : 0) + $entry['quantity'];
                if ($quantity > $choiceValue->quantity) {
                    throw ValidationException::withMessages([
                        'items' => ['A selected option does not have enough stock.'],
                    ]);
                }
                $item->quantity = $quantity;
                $item->price = $choiceValue->price;
                $item->save();
            }
        });

        return response()->json([
            'status' => 'success',
            'cart_items' => $cart->items()
                ->with(['product', 'choiceValue.typeValues.type'])
                ->get()
                ->map(fn (CartItem $item) => $this->formatItem($item)),
        ]);
    }

    private function formatItem(CartItem $item): array
    {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'image' => url("/api/productImage/{$item->product_id}"),
            'quantity' => (int) $item->quantity,
            'choice_value_id' => $item->choice_value_id,
            'price' => (float) ($item->choiceValue?->price ?? $item->price),
            'choiceDetails' => $item->choiceValue?->typeValues
                ->map(fn ($typeValue) => [
                    'type' => $typeValue->type->name,
                    'value' => $typeValue->value,
                    'colorCode' => $typeValue->colorCode,
                ])
                ->values() ?? [],
        ];
    }

    private function total($items): float
    {
        return (float) $items->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }
}
