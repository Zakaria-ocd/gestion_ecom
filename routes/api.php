<?php

use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\api\productsController;
use App\Http\Controllers\api\usersController;
use Illuminate\Support\Facades\Route;

Route::get("/products",[productsController::class,"index"]);
Route::get("/users",[usersController::class,"index"]);
Route::get("/users/{limit}",[usersController::class,"showUsers"]);
Route::get('/orders',[ordersController::class,'index']);
Route::get('/orders/{limit}',[ordersController::class,'showOrders']);