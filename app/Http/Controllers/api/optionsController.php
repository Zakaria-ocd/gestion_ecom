<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use \App\Models\Option;
use Illuminate\Http\Request;

class optionsController extends Controller
{
    public function index()
    {
        $options = Option::select('id', 'name')->get();
        return response()->json($options,200);
    }
    public function show(Request $request)
    {
        $id = $request->id;
        $option = Option::find($id);
        return response()->json($option,200);
    }
}
