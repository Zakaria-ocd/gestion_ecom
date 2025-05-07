<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Models\TypeValue;

class AvailableChoicesController extends Controller
{
    public function index()
    {
        $types = Type::all();
        $values = [];
        
        foreach ($types as $type) {
            $typeValues = TypeValue::where('type_id', $type->id)->get();
            $values[$type->id] = $typeValues->map(function ($value) {
                return [
                    'id' => $value->id,
                    'value' => $value->value
                ];
            });
        }
        
        return response()->json([
            'availableTypes' => $types->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name
                ];
            }),
            'availableValues' => $values
        ]);
    }
}