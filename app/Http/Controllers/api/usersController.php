<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class usersController extends Controller
{
    public function index(){

        return response()->json(User::all());
    }
    public function show(Request $request){

        return response()->json(User::where('id', $request->id)->first());
    }
    public function showUsers(Request $request){

        return response()->json(User::limit($request->limit)->get());
    }
    public function showImage(Request $request)
    {
        $filename = User::find($request->id)?->image;

        if (Storage::exists("users/{$filename}")) {
            $file = Storage::get("users/{$filename}");
            $mimeType = Storage::mimeType("users/{$filename}");
            return response($file, 200)->header('Content-Type', $mimeType);
        }
    }
    
}
