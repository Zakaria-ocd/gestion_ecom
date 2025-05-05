<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ordersController extends Controller
{
    public function index(){
        return response()->json(DB::table('orders')
        ->select('order_id','buyer_id',DB::raw('count(product_id) as products_total'),'username','role','email','orders.status',DB::raw('sum(order_items.price)As total_price'))
        ->join('users','buyer_id','=','users.id')
        ->join("order_items","order_items.order_id",'=','orders.id')
        ->groupBy('orders.id', 'users.username', 'orders.status', 'users.role', 'users.email', 'orders.status')
        ->get());
    }
    public function showOrders(Request $request){
       return response()->json(DB::table('orders')
        ->select('order_id','username','orders.status',DB::raw('sum(order_items.price)As total_price'))
        ->join('order_items','orders.id','=','order_items.order_id')
        ->join('users','buyer_id','=','users.id')
        ->groupBy('orders.id', 'order_items.order_id', 'users.username', 'orders.status')
        ->limit($request->limit)
        ->get());
    }
}
