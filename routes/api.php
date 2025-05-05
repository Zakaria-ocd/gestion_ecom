<?php

use App\Http\Controllers\api\optionsController;
use App\Http\Controllers\api\optionValuesController;
use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\api\productImagesController;
use App\Http\Controllers\api\productOptionsController;
use App\Http\Controllers\api\productsController;
use App\Http\Controllers\api\usersController;
use Illuminate\Support\Facades\Route;

Route::get("/users",[usersController::class,"index"]);
Route::get("/users/{limit}",[usersController::class,"showUsers"]);

Route::get("/products",[productsController::class,"index"]);
Route::post('/products/create', [productsController::class, 'store']);
Route::get('/categories', [productsController::class, 'getCategories']);

Route::get('/orders',[ordersController::class,'index']);
Route::get('/orders/{limit}',[ordersController::class,'showOrders']);

Route::get('/image/{filename}', [productImagesController::class, 'show']);
Route::get('/productImages/{productId}', [productImagesController::class, 'productImages']);
Route::post('/uploadImages', [productImagesController::class, 'store']);


Route::get('/options', [optionsController::class, 'index']);
Route::get('/options/{id}', [optionsController::class, 'show']);

Route::get('/optionValues', [optionValuesController::class, 'index']);
Route::get('/optionValues/{id}', [optionValuesController::class, 'show']);

Route::get('/productOptions', [productOptionsController::class, 'index']);
Route::post('/productOptions/create', [ProductOptionsController::class, 'store']);