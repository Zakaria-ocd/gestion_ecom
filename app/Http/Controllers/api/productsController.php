<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class productsController extends Controller
{
    public function index(){
    $products=Product::all();
    return response()->json(
             $products
    ,200);
   }
}
