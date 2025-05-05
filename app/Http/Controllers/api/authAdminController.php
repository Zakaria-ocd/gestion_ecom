<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class authAdminController extends Controller
{
    public function login(Request $request) {
        $user=User::where('email',$request->email)
        ->where('password',$request->password)
        ->where('role','admin')
        ->first();
        $token=$user->createToken($user->id);
        
        // $user->tokens()->delete();
        return response()->json([
            'token' => $token->plainTextToken,            
        ]);
        // return $user;
        }
    public function logout(Request $request) {
        $user=User::where('id',$request->user()->id)
        ->first();
        // $token=$user->createToken($user);
        
        $user->tokens()->delete();
        // return response()->json([
        //     'token' => $token->plainTextToken,
        //     'userEmail' =>$user->email,
            
        // ]);
        return response()->json(['ok'=>'success']);
        }
    public function checkAuth(Request $request){
        $user=User::where('id',$request->user()->id)
        ->where('email',$request->user()->email)
        ->where('role','admin')
        ->first();  
        if($user){
            return response()->json([
            'ok'=>'success','user'=>$request->user()
            ]
        );
        }
    }
}
