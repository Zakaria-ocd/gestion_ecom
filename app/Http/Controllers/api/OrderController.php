<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new order from the cart
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Validate shipping info
        $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'nullable|string',
            'phone' => 'required|string',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|in:cash_on_delivery,card'
        ]);
        
        // Get user's cart
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }
        
        // Calculate total
        $total = 0;
        foreach ($cart->items as $item) {
            $total += $item->price * $item->quantity;
        }
        
        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $total,
                'payment_method' => $request->payment_method ?? 'cash_on_delivery',
                'payment_status' => 'pending',
                'delivery_status' => 'pending',
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'notes' => $request->notes
            ]);
            
            // Create order items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'choice_id' => $item->choice_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->price * $item->quantity
                ]);
            }
            
            // Clear cart
            CartItem::where('cart_id', $cart->id)->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'total' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'message' => 'Order created successfully'
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user's orders
     */
    public function getUserOrders(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with('items.product', 'items.choice')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    
    /**
     * Get order details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with('items.product', 'items.choice')
            ->firstOrFail();
            
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
} 