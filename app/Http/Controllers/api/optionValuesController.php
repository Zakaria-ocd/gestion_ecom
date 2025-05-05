<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\OptionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class optionValuesController extends Controller
{
    public function index()
    {
        $optionValues = DB::table('option_values')
        ->join('options', 'option_values.option_id', '=', 'options.id')
        ->select('option_values.id', 'option_values.value',
            'options.id AS option_id', 'options.name')
        ->get();
        return response()->json($optionValues, 200);
    }
    public function show(Request $request)
    {
        $id = $request->id;
        $optionValues = DB::table('option_values')
        ->join('options', 'option_values.option_id', '=', 'options.id')
        ->where('option_values.option_id', '=', $id)
        ->select('option_values.id', 'option_values.value',
            'options.id AS option_id', 'options.name')
        ->get();;
        return response()->json($optionValues, 200);
    }
}
