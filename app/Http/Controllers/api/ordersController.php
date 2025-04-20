<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ordersController extends Controller
{
    public function index(){
        return response()->json(DB::table('orders')
        ->select('order_id','username','orders.status',DB::raw('sum(order_items.price)As total_price'))
        ->join('order_items','orders.id','=','order_items.order_id')
        ->join('users','buyer_id','=','users.id')
        ->groupBy('orders.id')
        ->get());
    }
    public function showOrders(Request $request){
       return response()->json(DB::table('orders')
        ->select('order_id','username','orders.status',DB::raw('sum(order_items.price)As total_price'))
        ->join('order_items','orders.id','=','order_items.order_id')
        ->join('users','buyer_id','=','users.id')
        ->groupBy('orders.id')
        ->limit($request->limit)
        ->get());
    }
}
