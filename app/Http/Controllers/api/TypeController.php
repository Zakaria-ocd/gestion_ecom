<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
    public function index()
    {
        $types = Type::all();
        return response()->json(['data' => $types]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:types',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = Type::create($request->all());

        return response()->json(['data' => $type, 'message' => 'Type created successfully'], 201);
    }

    public function show(Type $type)
    {
        return response()->json(['data' => $type]);
    }

    public function update(Request $request, Type $type)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:types,name,' . $type->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type->update($request->all());

        return response()->json(['data' => $type, 'message' => 'Type updated successfully']);
    }

    public function destroy(Type $type)
    {
        $type->delete();

        return response()->json(['message' => 'Type deleted successfully']);
    }
}