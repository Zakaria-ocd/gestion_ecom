<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class userController extends Controller
{
   function getUsers(){
    $users=[
        ["id"=>1,"nom"=>"Ouchouid","prenom"=>"Zakaria","age"=>18],
        ["id"=>2,"nom"=>"Loukhmi","prenom"=>"Abdelaziz","age"=>18],
        ["id"=>3,"nom"=>"Ouafik","prenom"=>"Mohammed","age"=>20],
        ["id"=>4,"nom"=>"Mahfoud","prenom"=>"Anass","age"=>18],
        ["id"=>5,"nom"=>"Idrissi","prenom"=>"Aymen","age"=>19]
    ];
    return response()->json([
             "success" => true,
            "message" => "User list retrieved successfully",
            "data" => $users
    ],200);
   }
}
