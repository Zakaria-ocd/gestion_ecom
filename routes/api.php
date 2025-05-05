<?php

use App\Http\Controllers\Api\authAdminController;
use App\Http\Controllers\api\ordersController;
use App\Http\Controllers\api\productImagesController;
use App\Http\Controllers\api\productsController;
use App\Http\Controllers\api\usersController;
use Illuminate\Support\Facades\Route;

Route::get("/products",[productsController::class,"index"]);
Route::get("/users",[usersController::class,"index"])->middleware(['auth:sanctum']);
Route::get("/users/{limit}",[usersController::class,"showUsers"]);
Route::get("/users/image/{id}",[usersController::class,"showImage"]);
Route::get("/users/image/email/{email}",[usersController::class,"showImageByemail"]);
Route::get('/orders',[ordersController::class,'index']);
Route::get('/orders/{limit}',[ordersController::class,'showOrders']);
Route::get('/image/{imageId}', [productImagesController::class, 'show']);
Route::post('/uploadImages', [productImagesController::class, 'store']);
Route::post('/createProduct', [productsController::class, 'storeProduct']);
Route::get('/categories', [productsController::class, 'getCategories']);
Route::post('/admin/login',[authAdminController::class,"login"]);
Route::post('/admin/checkAuth',[authAdminController::class,"checkAuth"])->middleware(['auth:sanctum']);
Route::post('/admin/logout',[authAdminController::class,"logout"])->middleware(['auth:sanctum']);