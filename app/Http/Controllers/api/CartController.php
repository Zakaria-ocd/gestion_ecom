<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ChoiceValue;
use App\Models\TypeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get the current user's cart items
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        $cartItems = $this->getFormattedCartItems($cart->id);
            
        return response()->json([
            'cart_items' => $cartItems,
            'cart_id' => $cart->id
        ]);
    }
    
    /**
     * Format cart items with all necessary details
     */
    private function getFormattedCartItems($cartId)
    {
        return CartItem::where('cart_id', $cartId)
            ->with(['product', 'product.images', 'choiceValue', 'choiceValue.typeValues'])
            ->get()
            ->map(function ($item) {
                // Get the first image for the product
                $image = $item->product->images->first();
                $imageUrl = $image ? url('/api/productImage/' . $item->product->id) : null;
                
                // Get choice value details if available
                $choiceValue = null;
                $choiceDetails = [];
                
                if ($item->choiceValue) {
                    $choiceValue = $item->choiceValue;
                    
                    // Extract type and value information
                    foreach ($choiceValue->typeValues as $typeValue) {
                        $choiceDetails[] = [
                            'type' => $typeValue->type->name,
                            'value' => $typeValue->value,
                            'colorCode' => $typeValue->pivot->colorCode
                        ];
                    }
                }
                
                return [
                    'id' => $item->id,
                    'productId' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'image' => $imageUrl,
                    'product' => $item->product,
                    'choiceValue' => $choiceValue,
                    'choiceDetails' => $choiceDetails,
                    'choice_value_id' => $item->choice_value_id
                ];
            });
    }
    
    /**
     * Add an item to the cart
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'choice_value_id' => 'nullable|exists:choice_values,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        // Check if product with the same choice already exists in cart
        $query = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id);
            
        if ($request->has('choice_value_id') && !empty($request->choice_value_id)) {
            $query->where('choice_value_id', $request->choice_value_id);
        } else {
            $query->whereNull('choice_value_id');
        }
        
        $cartItem = $query->first();
            
        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'choice_value_id' => !empty($request->choice_value_id) ? $request->choice_value_id : null,
                'quantity' => $request->quantity,
                'price' => $request->price,
            ]);
        }
        
        // Get detailed cart item
        $cartItemWithDetails = $this->getDetailedCartItem($cartItem);
        
        // Get all cart items for a complete response
        $allCartItems = $this->getFormattedCartItems($cart->id);
        
        return response()->json([
            'message' => 'Product added to cart',
            'cart_item' => $cartItemWithDetails,
            'cart_items' => $allCartItems
        ]);
    }
    
    /**
     * Get detailed information for a single cart item
     */
    private function getDetailedCartItem($cartItem)
    {
        // Get product details
        $product = Product::with('images')->find($cartItem->product_id);
        $image = $product->images->first();
        $imageUrl = $image ? url('/api/productImage/' . $product->id) : null;
        
        // Get choice value details if available
        $choiceValue = null;
        $choiceDetails = [];
        
        if ($cartItem->choice_value_id) {
            $choiceValue = ChoiceValue::with('typeValues.type')->find($cartItem->choice_value_id);
            
            if ($choiceValue) {
                // Extract type and value information
                foreach ($choiceValue->typeValues as $typeValue) {
                    $choiceDetails[] = [
                        'type' => $typeValue->type->name,
                        'value' => $typeValue->value,
                        'colorCode' => $typeValue->pivot->colorCode
                    ];
                }
            }
        }
        
        return [
            'id' => $cartItem->id,
            'productId' => $cartItem->product_id,
            'name' => $product->name,
            'price' => $cartItem->price,
            'quantity' => $cartItem->quantity,
            'image' => $imageUrl,
            'product' => $product,
            'choiceValue' => $choiceValue,
            'choiceDetails' => $choiceDetails,
            'choice_value_id' => $cartItem->choice_value_id
        ];
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }
        
        $cartItem = CartItem::where('id', $request->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();
            
        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }
        
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        
        // Get detailed cart item
        $cartItemWithDetails = $this->getDetailedCartItem($cartItem);
        
        // Get all cart items for a complete response
        $allCartItems = $this->getFormattedCartItems($cart->id);
        
        return response()->json([
            'message' => 'Cart updated successfully',
            'cart_item' => $cartItemWithDetails,
            'cart_items' => $allCartItems
        ]);
    }
    
    /**
     * Remove an item from the cart
     */
    public function removeFromCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_item_id' => 'required|exists:cart_items,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }
        
        $cartItem = CartItem::where('id', $request->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();
            
        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }
        
        // Save ID for response
        $removedItemId = $cartItem->id;
        
        // Delete the item
        $cartItem->delete();
        
        // Get all remaining cart items
        $remainingItems = $this->getFormattedCartItems($cart->id);
        
        return response()->json([
            'message' => 'Item removed from cart',
            'removed_item_id' => $removedItemId,
            'cart_items' => $remainingItems
        ]);
    }
    
    /**
     * Merge localStorage cart with database cart
     */
    public function mergeCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.choice_value_id' => 'nullable|exists:choice_values,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        DB::beginTransaction();
        
        try {
            foreach ($request->items as $item) {
                // Check if product with same choice already exists in cart
                $query = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $item['product_id']);
                
                if (isset($item['choice_value_id']) && !empty($item['choice_value_id'])) {
                    $query->where('choice_value_id', $item['choice_value_id']);
                } else {
                    $query->whereNull('choice_value_id');
                }
                
                $cartItem = $query->first();
                    
                if ($cartItem) {
                    // Update quantity
                    $cartItem->quantity += $item['quantity'];
                    $cartItem->save();
                } else {
                    // Create new cart item with explicit null for choice_value_id if not set
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $item['product_id'],
                        'choice_value_id' => isset($item['choice_value_id']) && !empty($item['choice_value_id']) 
                            ? $item['choice_value_id'] 
                            : null,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            }
            
            DB::commit();
            
            // Return the updated cart with product details
            $cartItems = $this->getFormattedCartItems($cart->id);
                
            return response()->json([
                'message' => 'Cart merged successfully',
                'cart_items' => $cartItems,
                'cart_id' => $cart->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to merge cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 