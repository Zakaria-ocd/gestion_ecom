<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Models\TypeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeValueController extends Controller
{
    public function index(Type $type)
    {
        $values = TypeValue::where('type_id', $type->id)->get();
        return response()->json(['data' => $values]);
    }

    public function store(Request $request, Type $type)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $typeValue = $type->values()->create($request->all());

        return response()->json(['data' => $typeValue, 'message' => 'Type value created successfully'], 201);
    }

    public function show(Type $type, TypeValue $value)
    {
        return response()->json(['data' => $value]);
    }

    public function update(Request $request, Type $type, TypeValue $value)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $value->update($request->all());

        return response()->json(['data' => $value, 'message' => 'Type value updated successfully']);
    }

    public function destroy(Type $type, TypeValue $value)
    {
        $value->delete();

        return response()->json(['message' => 'Type value deleted successfully']);
    }
}