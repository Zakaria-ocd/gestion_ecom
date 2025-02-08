<?php

use App\Http\Controllers\api\userController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user",[userController::class,"getUsers"]);