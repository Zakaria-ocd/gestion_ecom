<?php

use App\Http\Controllers\Api\authAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailableChoicesController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\api\categoriesController;
use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\Api\ProductChoiceController;
use App\Http\Controllers\api\productImagesController;
use App\Http\Controllers\Api\productsController;
use App\Http\Controllers\api\TypeController;
use App\Http\Controllers\api\TypeValueController;
use App\Http\Controllers\api\usersController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/login', [authAdminController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [productsController::class, 'index']);
Route::get('/products/{product}', [productsController::class, 'show']);
Route::get('/types', [TypeController::class, 'index']);
Route::get('/types/{type}', [TypeController::class, 'show']);
Route::get('/types/{type}/values', [TypeValueController::class, 'index']);
Route::get('/types/{type}/values/{value}', [TypeValueController::class, 'show']);
Route::get('/available-choices', [AvailableChoicesController::class, 'index']);
Route::get('/products/{product}/choices', [ProductChoiceController::class, 'index']);
Route::get('/showProducts', [productsController::class, 'getProducts']);
Route::get('/productDefaultPrice/{product_id}', [productsController::class, 'getProductDefaultPrice']);
Route::get('/productImage/{product_id}', [productImagesController::class, 'getProductImage']);
Route::get('/productOptions/{product_id}', [productsController::class, 'getProductOptions']);
Route::get('/getProductChoices/{product_id}', [productsController::class, 'getProductChoices']);
Route::get('/categories', [categoriesController::class, 'index']);
Route::get('/categories/{id}', [categoriesController::class, 'show']);
Route::get('/categories/{id}/products', [categoriesController::class, 'products']);
Route::get('/image/{filename}', [productImagesController::class, 'show']);
Route::get('/productImages/{productId}', [productImagesController::class, 'productImages']);
Route::get('/filter-products', [productsController::class, 'filterProducts']);
Route::get('/users/imageById/{id}', [usersController::class, 'showImageById']);
Route::get('/users/image/{image}', [usersController::class, 'showImage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/users/{id}', [usersController::class, 'updateUser']);
    Route::post('/users/image', [usersController::class, 'uploadImage']);

    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::put('/cart/update', [CartController::class, 'updateCart']);
    Route::delete('/cart/remove', [CartController::class, 'removeFromCart']);
    Route::post('/cart/merge', [CartController::class, 'mergeCart']);
    Route::post('/orders', [ordersController::class, 'store']);
    Route::get('/orders/{limit}/limit', [ordersController::class, 'userOrders']);
    Route::get('/orders/{id}', [ordersController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/checkAuth', [authAdminController::class, 'checkAuth']);
    Route::post('/admin/logout', [authAdminController::class, 'logout']);
    Route::get('/admin/dashboard/stats', [authAdminController::class, 'dashboardStats']);
    Route::get('/admin/orders/{limit}', [ordersController::class, 'showOrders']);
    Route::get('/admin/users/{limit}', [usersController::class, 'showUsers']);

    Route::post('/products', [productsController::class, 'store']);
    Route::put('/products/{product}', [productsController::class, 'update']);
    Route::delete('/products/{product}', [productsController::class, 'destroy']);
    Route::post('/products/{product}/choices', [ProductChoiceController::class, 'store']);
    Route::put('/products/{product}/choices/{choice}', [ProductChoiceController::class, 'update']);
    Route::delete('/products/{product}/choices/{choice}', [ProductChoiceController::class, 'destroy']);

    Route::post('/types', [TypeController::class, 'store']);
    Route::put('/types/{type}', [TypeController::class, 'update']);
    Route::delete('/types/{type}', [TypeController::class, 'destroy']);
    Route::post('/types/{type}/values', [TypeValueController::class, 'store']);
    Route::put('/types/{type}/values/{value}', [TypeValueController::class, 'update']);
    Route::delete('/types/{type}/values/{value}', [TypeValueController::class, 'destroy']);

    Route::post('/categories', [categoriesController::class, 'store']);
    Route::put('/categories', [categoriesController::class, 'update']);
    Route::delete('/categories', [categoriesController::class, 'destroy']);

    Route::get('/users', [usersController::class, 'index']);
    Route::get('/users/{id}', [usersController::class, 'show']);
    Route::get('/users/{limit}/limit', [usersController::class, 'showUsers']);
    Route::delete('/users/{id}', [usersController::class, 'deleteUser']);
    Route::delete('/users/image/{id}', [usersController::class, 'deleteImage']);
    Route::get('/users/{user_id}/orders', [ordersController::class, 'getUserOrdersById']);

    Route::get('/orders', [ordersController::class, 'index']);
    Route::put('/orders/{id}', [ordersController::class, 'update']);
    Route::delete('/orders/{id}', [ordersController::class, 'destroy']);
    Route::post('/uploadImages', [productImagesController::class, 'store']);
});
