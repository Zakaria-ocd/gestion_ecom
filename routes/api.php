<?php

use App\Http\Controllers\api\categoriesController;
use App\Http\Controllers\Api\authAdminController;
use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\api\productImagesController;
use App\Http\Controllers\api\productsController;
use App\Http\Controllers\api\usersController;
use App\Http\Controllers\api\AvailableChoicesController;
use App\Http\Controllers\api\ProductChoiceController;
use App\Http\Controllers\api\TypeController;
use App\Http\Controllers\api\TypeValueController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', productsController::class);

Route::apiResource('types', TypeController::class);

Route::apiResource('types.values', TypeValueController::class);

Route::get('available-choices', [AvailableChoicesController::class, 'index']);

Route::get('products/{product}/choices', [ProductChoiceController::class, 'index']);
Route::post('products/{product}/choices', [ProductChoiceController::class, 'store']);
Route::put('products/{product}/choices/{choice}', [ProductChoiceController::class, 'update']);
Route::delete('products/{product}/choices/{choice}', [ProductChoiceController::class, 'destroy']);
Route::get("/showProducts",[productsController::class,"getProducts"]);
Route::get("/productDefaultPrice/{product_id}",[productsController::class,"getProductDefaultPrice"]);
Route::get("/productImage/{product_id}",[productsController::class,"getProductImage"]);
Route::get("/productOptions/{product_id}",[productsController::class,"getProductOptions"]);
Route::get("/getProductChoices/{product_id}",[productsController::class,"getProductChoices"]);

Route::apiResource('products', productsController::class);

Route::apiResource('types', TypeController::class);

Route::apiResource('types.values', TypeValueController::class);

Route::get('available-choices', [AvailableChoicesController::class, 'index']);

Route::get('products/{product}/choices', [ProductChoiceController::class, 'index']);
Route::post('products/{product}/choices', [ProductChoiceController::class, 'store']);
Route::put('products/{product}/choices/{choice}', [ProductChoiceController::class, 'update']);
Route::delete('products/{product}/choices/{choice}', [ProductChoiceController::class, 'destroy']);

Route::get("/users",[usersController::class,"index"]);
Route::get("/users/{id}",[usersController::class,"show"]);
Route::get("/users",[usersController::class,"index"])->middleware(['auth:sanctum']);
Route::get("/users/{limit}",[usersController::class,"showUsers"]);
Route::get("/users/image/{id}",[usersController::class,"showImage"]);

Route::post('/admin/login',[authAdminController::class,"login"]);
Route::post('/admin/checkAuth',[authAdminController::class,"checkAuth"])->middleware(['auth:sanctum']);
Route::post('/admin/logout',[authAdminController::class,"logout"])->middleware(['auth:sanctum']);

Route::get('/categories', [categoriesController::class, 'index']);
Route::get('/categories/{id}', [categoriesController::class, 'show']);

Route::get('/image/{filename}', [productImagesController::class, 'show']);
Route::get('/productImages/{productId}', [productImagesController::class, 'productImages']);
Route::post('/uploadImages', [productImagesController::class, 'store']);

Route::get('/orders',[ordersController::class,'index']);
Route::get('/orders/{limit}',[ordersController::class,'showOrders']);
