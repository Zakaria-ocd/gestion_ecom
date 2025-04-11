<?php

use App\Http\Controllers\api\productsController;
use Illuminate\Support\Facades\Route;

Route::get("/products",[productsController::class,"getProducts"]);