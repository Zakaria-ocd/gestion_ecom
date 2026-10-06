<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ordersController extends Controller
{
    public function index()
    {
        return response()->json(
            $this->formatOrders(
                Order::with(['user', 'items.product', 'items.choiceValue.typeValues.type'])
                    ->latest()
                    ->get()
            )
        );
    }

    public function userOrders(Request $request, $limit = 100)
    {
        $orders = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(max(1, min((int) $limit, 100)))
            ->get();

        return response()->json($this->formatOrders($orders));
    }

    public function getUserOrdersById($user_id)
    {
        if (! User::whereKey($user_id)->exists()) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $orders = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
            ->where('user_id', $user_id)
            ->latest()
            ->get();

        return response()->json($this->formatOrders($orders));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?(?=(?:\D*\d){7,15}\D*$)[0-9\s().-]+$/',
            ],
            'payment_method' => ['required', Rule::in(['cash_on_delivery'])],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (! $cart) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        $cartItems = $cart->items()->with('choiceValue')->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        $lineItems = $cartItems->map(function (CartItem $item) {
            $unitPrice = (float) ($item->choiceValue?->price ?? $item->price);

            return [
                'item' => $item,
                'price' => $unitPrice,
                'total' => $unitPrice * $item->quantity,
            ];
        });

        $order = DB::transaction(function () use ($lineItems, $cart, $request, $validated) {
            $total = $lineItems->sum('total');

            $order = Order::create([
                'user_id' => $request->user()->id,
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'delivery_status' => 'pending',
                'recipient_name' => $validated['recipient_name'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lineItems as $lineItem) {
                $item = $lineItem['item'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'choice_value_id' => $item->choice_value_id,
                    'quantity' => $item->quantity,
                    'price' => $lineItem['price'],
                    'total' => $lineItem['total'],
                ]);
            }

            $cart->items()->delete();

            return $order;
        });

        $order->load(['items.product', 'items.choiceValue.typeValues.type']);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $this->formatOrder($order),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product', 'items.choiceValue.typeValues.type'])
            ->find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($request->user()->role !== 'admin' && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($this->formatOrder($order));
    }

    public function showOrders($limit = 5)
    {
        $orders = Order::with('user')
            ->latest()
            ->limit(max(1, min((int) $limit, 100)))
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_id' => $order->id,
                'username' => $order->user?->username,
                'status' => $order->delivery_status,
                'total_price' => (float) $order->total_amount,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at,
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'delivery_status' => ['sometimes', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'payment_status' => ['sometimes', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
        ]);

        if (isset($validated['status']) || isset($validated['delivery_status'])) {
            $order->delivery_status = $validated['delivery_status'] ?? $validated['status'];
        }
        if (isset($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }

        $order->save();

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $this->formatOrder($order->load(['items.product', 'items.choiceValue.typeValues.type'])),
        ]);
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    private function formatOrders($orders)
    {
        return $orders->map(fn (Order $order) => $this->formatOrder($order));
    }

    private function formatOrder(Order $order): Order
    {
        $order->setRelation('items', $order->items->map(function (OrderItem $item) {
            $item->choiceDetails = $item->choiceValue?->typeValues
                ->map(fn ($typeValue) => [
                    'type' => $typeValue->type->name,
                    'value' => $typeValue->value,
                    'colorCode' => $typeValue->colorCode,
                ])
                ->values() ?? collect();

            return $item;
        }));

        return $order;
    }
}
