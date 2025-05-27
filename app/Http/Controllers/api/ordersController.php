<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ChoiceValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ordersController extends Controller
{
    /**
     * Display all orders (admin access).
     */
    public function index()
    {
        return response()->json(DB::table('orders')
            ->select('orders.id as order_id', 'user_id',
                DB::raw('count(product_id) as products_total'), 'username', 'role', 'email', 'orders.status',
                DB::raw('sum(order_items.price) As total_price'))
            ->join('users', 'user_id', '=', 'users.id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->groupBy('orders.id', 'users.username', 'orders.status', 'users.role', 'users.email', 'user_id')
        ->get());
    }

    /**
     * Get orders for the authenticated user.
     */
    public function userOrders(Request $request)
    {
        $user = $request->user();
        $orders = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                // Process each order to format choice value information
                $order->items = $order->items->map(function ($item) {
                    $choiceDetails = [];
                    
                    if ($item->choiceValue) {
                        foreach ($item->choiceValue->typeValues as $typeValue) {
                            $choiceDetails[] = [
                                'type' => $typeValue->type->name,
                                'value' => $typeValue->value,
                                'colorCode' => $typeValue->pivot->colorCode
                            ];
                        }
                    }
                    
                    $item->choiceDetails = $choiceDetails;
                    return $item;
                });
                
                return $order;
            });
            
        return response()->json($orders);
    }

    /**
     * Get orders for a specific user by ID (for admin access).
     */
    public function getUserOrdersById($user_id)
    {
        // Check if user exists
        $user = \App\Models\User::find($user_id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        try {
            $orders = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
                ->where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Handle case where user has no orders
            if ($orders->isEmpty()) {
                return response()->json([], 200); // Return empty array with 200 OK status
            }
            
            $orders = $orders->map(function ($order) {
                // Process each order to format choice value information
                $order->items = $order->items->map(function ($item) {
                    $choiceDetails = [];
                    
                    if ($item->choiceValue) {
                        foreach ($item->choiceValue->typeValues as $typeValue) {
                            $choiceDetails[] = [
                                'type' => $typeValue->type->name,
                                'value' => $typeValue->value,
                                'colorCode' => $typeValue->pivot->colorCode
                            ];
                        }
                    }
                    
                    $item->choiceDetails = $choiceDetails;
                    return $item;
                });
                
                return $order;
            });
                
            return response()->json($orders);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching user orders: ' . $e->getMessage());
            
            // Check if it's a column not found error
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                return response()->json([
                    'message' => 'Database schema error: ' . $e->getMessage()
                ], 500);
            }
            
            return response()->json([
                'message' => 'Failed to fetch user orders'
            ], 500);
        }
    }

    /**
     * Store a new order for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|string|in:cash_on_delivery',
            'total_price' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        // Check if user has items in cart
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }
        
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $request->total_price,
                'status' => 'pending',
                'address' => $request->address,
                'phone' => $request->phone,
                'payment_method' => $request->payment_method,
            ]);
            
            // Create order items from cart items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'choice_value_id' => $cartItem->choice_value_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                ]);
            }
            
            // Clear user's cart
            CartItem::where('cart_id', $cart->id)->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load(['items.product', 'items.choiceValue.typeValues.type'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        
        // Process choice value information
        $order->items = $order->items->map(function ($item) {
            $choiceDetails = [];
            
            if ($item->choiceValue) {
                foreach ($item->choiceValue->typeValues as $typeValue) {
                    $choiceDetails[] = [
                        'type' => $typeValue->type->name,
                        'value' => $typeValue->value,
                        'colorCode' => $typeValue->pivot->colorCode
                    ];
                }
            }
            
            $item->choiceDetails = $choiceDetails;
            return $item;
        });
        
        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Display a limited number of orders (for admin dashboard).
     */
    public function showOrders($limit = 5)
    {
       return response()->json(DB::table('orders')
            ->select('orders.id as order_id', 'username', 'orders.status', DB::raw('sum(order_items.price) As total_price'))
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('users', 'user_id', '=', 'users.id')
        ->groupBy('orders.id', 'order_items.order_id', 'users.username', 'orders.status')
            ->limit($limit)
        ->get());
    }
}
