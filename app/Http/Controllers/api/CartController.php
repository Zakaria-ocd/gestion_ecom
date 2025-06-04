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
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Get all cart items for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {
        $user = $request->user();
            $cartItems = [];
            $totalPrice = 0;
            
            if ($user && $user->cart) {
                
                $items = $user->cart->items()
                    ->with([
                        'product', 
                        'choiceValue',
                        'choiceValue.typeValues',
                        'choiceValue.typeValues.type'
                    ])
                    ->get();
                    
                
                foreach ($items as $item) {
                    $detailedItem = $this->getDetailedCartItem($item);
                    $cartItems[] = $detailedItem;
                    
                    
                    $price = $detailedItem['price'] ?? 0;
                    $totalPrice += $price * $item->quantity;
                }
            }
            
        return response()->json([
                'status' => 'success',
            'cart_items' => $cartItems,
                'total_price' => $totalPrice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve cart: ' . $e->getMessage()
            ], 500);
        }
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
                
                $image = $item->product->images->first();
                $imageUrl = $image ? url('/api/productImage/' . $item->product->id) : null;
                
                
                $choiceValue = null;
                $choiceDetails = [];
                
                if ($item->choiceValue) {
                    $choiceValue = $item->choiceValue;
                    
                    
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
     * Add a product to cart
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addToCart(Request $request)
    {
        try {
            
            $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
                'choice_value_id' => 'nullable|exists:choice_values,id',
            ]);

            
            $user = $request->user();
            $cart = $user ? $user->cart : null;
            
            if (!$cart) {
                $cart = Cart::create([
                    'user_id' => $user ? $user->id : null,
                    'session_id' => $user ? null : session()->getId()
                ]);
                
                if ($user) {
                    $user->cart_id = $cart->id;
                    $user->save();
                }
            }
            
            
            $product = Product::find($validatedData['product_id']);
            if (!$product) {
                throw new \Exception("Product not found");
        }
        
            
            $price = $product->default_price ?? 0;
            if (!empty($validatedData['choice_value_id'])) {
                $choiceValue = ChoiceValue::find($validatedData['choice_value_id']);
                if ($choiceValue) {
                    
                    $price = $choiceValue->price ?: $price;
                }
            }
            
            
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $validatedData['product_id'])
                ->where(function($query) use ($validatedData) {
                    if (isset($validatedData['choice_value_id'])) {
                        $query->where('choice_value_id', $validatedData['choice_value_id']);
        } else {
            $query->whereNull('choice_value_id');
        }
                })
                ->first();
                
            
            if ($existingItem) {
                $existingItem->quantity += $validatedData['quantity'];
                $existingItem->save();
                $cartItem = $existingItem;
        } else {
                
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                    'product_id' => $validatedData['product_id'],
                    'quantity' => $validatedData['quantity'],
                    'choice_value_id' => $validatedData['choice_value_id'] ?? null,
                    'price' => $price
            ]);
        }
        
            
            $detailedItem = $this->getDetailedCartItem($cartItem);
        
        return response()->json([
                'status' => 'success',
            'message' => 'Product added to cart',
                'cart_item' => $detailedItem
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to cart: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get detailed cart item information for display in frontend
     * 
     * @param CartItem $cartItem The cart item to format
     * @return array Formatted cart item with product details
     */
    private function getDetailedCartItem($cartItem)
    {
        
        if (!$cartItem->relationLoaded('product')) {
            $cartItem->load('product');
        }
        
        if (!$cartItem->relationLoaded('choiceValue')) {
            $cartItem->load([
                'choiceValue', 
                'choiceValue.typeValues', 
                'choiceValue.typeValues.type'
            ]);
        }
        
        
        $cartItemData = [
            'id' => $cartItem->id,
            'product_id' => $cartItem->product_id,
            'quantity' => $cartItem->quantity,
            'choice_value_id' => $cartItem->choice_value_id
        ];
        
        
        if ($cartItem->product) {
            $cartItemData['product_name'] = $cartItem->product->name;
            $cartItemData['image'] = url("/api/productImage/{$cartItem->product_id}");
        
            
            if ($cartItem->choice_value_id && $cartItem->choiceValue && $cartItem->choiceValue->price) {
                $cartItemData['price'] = $cartItem->choiceValue->price;
            } else {
                $cartItemData['price'] = $cartItem->price ?? $cartItem->product->default_price ?? 0;
            }
        }
        
        
        $choiceDetails = [];
        if ($cartItem->choice_value_id && $cartItem->choiceValue) {
            
            if (!$cartItem->choiceValue->relationLoaded('typeValues')) {
                $cartItem->choiceValue->load(['typeValues.type']);
            }
            
            $typeValues = $cartItem->choiceValue->typeValues;
            
            if ($typeValues && $typeValues->count() > 0) {
                foreach ($typeValues as $typeValue) {
                    
                    if (!$typeValue->relationLoaded('type')) {
                        $typeValue->load('type');
                    }
                    
                    
                    $typeName = $typeValue->type ? $typeValue->type->name : 'Attribute';
                    
                    
                    $colorCode = null;
                    if ($typeValue->pivot && isset($typeValue->pivot->colorCode)) {
                        $colorCode = $typeValue->pivot->colorCode;
                    } else if (isset($typeValue->colorCode)) {
                        $colorCode = $typeValue->colorCode;
                    }
                    
                    $choiceDetails[] = [
                        'type' => $typeName,
                        'value' => $typeValue->value,
                        'colorCode' => $colorCode
                    ];
                }
            }
        }
        
        $cartItemData['choiceDetails'] = $choiceDetails;

        return $cartItemData;
    }
    
    /**
     * Update cart item quantity
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCart(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'cart_item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $user = $request->user();
        
            if (!$user || !$user->cart) {
            return response()->json([
                    'status' => 'error',
                'message' => 'Cart not found'
            ], 404);
        }
        
            
            $cartItem = CartItem::where('id', $validatedData['cart_item_id'])
                ->where('cart_id', $user->cart->id)
            ->first();
            
        if (!$cartItem) {
            return response()->json([
                    'status' => 'error',
                    'message' => 'Item not found in cart'
            ], 404);
        }
        
            
            $cartItem->quantity = $validatedData['quantity'];
        $cartItem->save();
        
            
            $cartItem->load([
                'product',
                'choiceValue',
                'choiceValue.typeValues.type'
            ]);
            
            
            $updatedItem = $this->getDetailedCartItem($cartItem);
        
            
            $updatedItems = $user->cart->items()
                ->with([
                    'product', 
                    'choiceValue',
                    'choiceValue.typeValues',
                    'choiceValue.typeValues.type'
                ])
                ->get()
                ->map(function($item) {
                    return $this->getDetailedCartItem($item);
                });
            
            
            $totalPrice = $updatedItems->sum(function($item) {
                return ($item['price'] ?? 0) * $item['quantity'];
            });
        
        return response()->json([
                'status' => 'success',
                'message' => 'Cart updated',
                'cart_item' => $updatedItem,
                'cart_items' => $updatedItems,
                'total_price' => $totalPrice
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update cart: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove an item from cart
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeFromCart(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'cart_item_id' => 'required|integer',
                'choice_value_id' => 'nullable|integer'
            ]);
        
        $user = $request->user();
        
            if (!$user || !$user->cart) {
            return response()->json([
                    'status' => 'error',
                'message' => 'Cart not found'
            ], 404);
        }
        
            
            $query = CartItem::where('id', $validatedData['cart_item_id'])
                ->where('cart_id', $user->cart->id);
            
            
            if (isset($validatedData['choice_value_id'])) {
                $query->where('choice_value_id', $validatedData['choice_value_id']);
            }
            
            
            $deleted = $query->delete();
            
            if (!$deleted) {
            return response()->json([
                    'status' => 'error',
                    'message' => 'Item not found in cart'
            ], 404);
        }
        
            
            $updatedItems = $user->cart->items()
                ->with([
                    'product', 
                    'choiceValue',
                    'choiceValue.typeValues',
                    'choiceValue.typeValues.type'
                ])
                ->get()
                ->map(function($item) {
                    return $this->getDetailedCartItem($item);
                });
        
            
            $totalPrice = $updatedItems->sum(function($item) {
                return ($item['price'] ?? 0) * $item['quantity'];
            });
        
        return response()->json([
                'status' => 'success',
            'message' => 'Item removed from cart',
                'cart_items' => $updatedItems,
                'total_price' => $totalPrice
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove item from cart: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Merge guest cart items with user's cart after login
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function mergeCart(Request $request)
    {
        try {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
                'items.*.choice_value_id' => 'nullable|exists:choice_values,id',
                'items.*.price' => 'nullable|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                    'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            
            $cart = $user->cart;
            if (!$cart) {
                $cart = Cart::create(['user_id' => $user->id]);
                $user->cart_id = $cart->id;
                $user->save();
            }
            
            
            foreach ($request->items as $item) {
                
                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue; 
                }
                
                
                $price = $item['price'] ?? null;
                
                if (!$price) {
                    
                    $price = $product->default_price ?? 0;
                    
                    
                    if (!empty($item['choice_value_id'])) {
                        $choiceValue = ChoiceValue::find($item['choice_value_id']);
                        if ($choiceValue && $choiceValue->price) {
                            $price = $choiceValue->price;
                        }
                    }
                }
                
                
                $existingItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $item['product_id'])
                    ->where(function($query) use ($item) {
                        if (isset($item['choice_value_id'])) {
                    $query->where('choice_value_id', $item['choice_value_id']);
                } else {
                    $query->whereNull('choice_value_id');
                }
                    })
                    ->first();
                    
                if ($existingItem) {
                    
                    $existingItem->quantity += $item['quantity'];
                    $existingItem->save();
                } else {
                    
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'choice_value_id' => $item['choice_value_id'] ?? null,
                        'price' => $price
                    ]);
                }
            }
            
            
            $updatedItems = $user->cart->items()
                ->with([
                    'product', 
                    'choiceValue',
                    'choiceValue.typeValues',
                    'choiceValue.typeValues.type'
                ])
                ->get()
                ->map(function($item) {
                    return $this->getDetailedCartItem($item);
                });
                
            
            $totalPrice = $updatedItems->sum(function($item) {
                return ($item['price'] ?? 0) * $item['quantity'];
            });
                
            return response()->json([
                'status' => 'success',
                'message' => 'Cart merged successfully',
                'cart_items' => $updatedItems,
                'total_price' => $totalPrice
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to merge cart: ' . $e->getMessage()
            ], 500);
        }
    }
} 