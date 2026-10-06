<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class authAdminController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();
        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'error' => 'Not authorized as admin',
            ], 403);
        }

        $token = $user->createToken($user->id);

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $user = User::where('id', $request->user()->id)
            ->first();

        $user->tokens()->delete();

        return response()->json(['ok' => 'success']);
    }

    public function checkAuth(Request $request)
    {
        $user = User::where('id', $request->user()->id)
            ->where('email', $request->user()->email)
            ->where('role', 'admin')
            ->first();

        if ($user) {
            return response()->json([
                'ok' => 'success', 'user' => $request->user(),
            ]
            );
        }

        return response()->json(['message' => 'Not authorized as admin'], 403);
    }

    public function dashboardStats()
    {
        $totalSales = Order::where('delivery_status', 'delivered')->sum('total_amount');
        $totalProducts = Product::count();
        $totalSellers = User::where('role', 'seller')->count();
        $totalOrders = Order::count();

        return response()->json([
            'totalSales' => $totalSales,
            'totalProducts' => $totalProducts,
            'totalSellers' => $totalSellers,
            'totalOrders' => $totalOrders,
        ]);
    }
}
