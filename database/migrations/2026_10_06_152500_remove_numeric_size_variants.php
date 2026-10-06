<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $numericSizeIds = DB::table('type_values')
                ->join('types', 'types.id', '=', 'type_values.type_id')
                ->whereRaw('LOWER(types.name) = ?', ['size'])
                ->get(['type_values.id', 'type_values.value'])
                ->filter(fn ($value) => preg_match('/^\d+(?:[.,]\d+)?$/D', trim($value->value)))
                ->pluck('id');

            if ($numericSizeIds->isEmpty()) {
                return;
            }

            $choiceValueIds = DB::table('type_value_choice_value')
                ->whereIn('type_value_id', $numericSizeIds)
                ->distinct()
                ->pluck('choice_value_id');

            DB::table('choice_values')->whereIn('id', $choiceValueIds)->delete();
            DB::table('type_values')->whereIn('id', $numericSizeIds)->delete();
        });
    }

    public function down(): void
    {
        // Removed numeric size variants cannot be restored without their original product data.
    }
};
