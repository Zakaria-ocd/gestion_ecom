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
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
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
Route::get("/productImage/{product_id}",[productImagesController::class,"getProductImage"]);
Route::get("/productOptions/{product_id}",[productsController::class,"getProductOptions"]);
Route::get("/getProductChoices/{product_id}",[productsController::class,"getProductChoices"]);

Route::get("/users",[usersController::class,"index"]);
Route::get("/users/{id}",[usersController::class,"show"]);
Route::get("/users",[usersController::class,"index"]);
Route::get("/users/{limit}/limit",[usersController::class,"showUsers"]);
Route::put("/users/{id}",[usersController::class,"updateUser"]);
Route::delete("/users/{id}",[usersController::class,"deleteUser"]);
Route::get("/users/imageById/{id}",[usersController::class,"showImageById"]);
Route::get("/users/image/{image}",[usersController::class,"showImage"]);
Route::post("/users/image",[usersController::class,"uploadImage"]);
Route::delete("/users/image/{id}",[usersController::class,"deleteImage"]);
Route::get("/users/{user_id}/orders",[ordersController::class,"getUserOrdersById"]);

Route::post('/admin/login',[authAdminController::class,"login"]);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/checkAuth',[authAdminController::class,"checkAuth"]);
    Route::post('/admin/logout',[authAdminController::class,"logout"]);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/update', [CartController::class, 'updateCart']);
    Route::delete('/cart/remove', [CartController::class, 'removeFromCart']);
    Route::post('/cart/merge', [CartController::class, 'mergeCart']);

    Route::get('/orders',[ordersController::class,'index']);
    Route::post('/orders', [ordersController::class, 'store']);
    Route::get('/orders/{limit}/limit', [ordersController::class, 'userOrders']);
    Route::get('/orders/{id}', [ordersController::class, 'show']);
});

Route::get('/categories', [categoriesController::class, 'index']);
Route::get('/categories/{id}', [categoriesController::class, 'show']);
Route::get('/categories/{id}/products', [categoriesController::class, 'products']);

Route::get('/image/{filename}', [productImagesController::class, 'show']);
Route::get('/productImages/{productId}', [productImagesController::class, 'productImages']);
Route::post('/uploadImages', [productImagesController::class, 'store']);
