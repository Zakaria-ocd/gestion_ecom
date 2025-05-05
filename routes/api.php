<?php

use App\Http\Controllers\api\optionsController;
use App\Http\Controllers\api\optionValuesController;
use App\Http\Controllers\Api\authAdminController;
use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\api\productImagesController;
use App\Http\Controllers\api\productOptionsController;
use App\Http\Controllers\api\productsController;
use App\Http\Controllers\api\usersController;
use Illuminate\Support\Facades\Route;

Route::get("/users",[usersController::class,"index"]);
Route::get("/users",[usersController::class,"index"])->middleware(['auth:sanctum']);
Route::get("/users/{limit}",[usersController::class,"showUsers"]);
Route::get("/users/image/{id}",[usersController::class,"showImage"]);

Route::post('/admin/login',[authAdminController::class,"login"]);
Route::post('/admin/checkAuth',[authAdminController::class,"checkAuth"])->middleware(['auth:sanctum']);
Route::post('/admin/logout',[authAdminController::class,"logout"])->middleware(['auth:sanctum']);

Route::get("/products",[productsController::class,"index"]);
Route::post('/products/create', [productsController::class, 'store']);

Route::get('/options', [optionsController::class, 'index']);
Route::get('/options/{id}', [optionsController::class, 'show']);
Route::get('/productOptions', [productOptionsController::class, 'index']);

Route::get('/optionValues', [optionValuesController::class, 'index']);
Route::get('/optionValues/{id}', [optionValuesController::class, 'show']);

Route::get('/categories', [productsController::class, 'getCategories']);


Route::get('/image/{filename}', [productImagesController::class, 'show']);
Route::get('/productImages/{productId}', [productImagesController::class, 'productImages']);
Route::post('/uploadImages', [productImagesController::class, 'store']);

Route::get('/orders',[ordersController::class,'index']);
Route::get('/orders/{limit}',[ordersController::class,'showOrders']);
