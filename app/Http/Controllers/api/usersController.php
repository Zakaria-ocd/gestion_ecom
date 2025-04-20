<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class usersController extends Controller
{
    public function index(){

        return response()->json(User::all());
    }
    public function showUsers(Request $request){

        return response()->json(User::limit($request->limit)->get());
    }
}
