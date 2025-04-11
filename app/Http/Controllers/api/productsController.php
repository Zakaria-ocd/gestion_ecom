<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;

class productsController extends Controller
{
    public function getProducts(){
    $products=Products::all();
    return response()->json([
             "success" => true,
            "message" => "Products list retrieved successfully",
            "data" => $products
    ],200);
   }
}
