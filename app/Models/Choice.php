<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Choice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'choice_values_id'];

    /**
     * Get the product that owns the choice.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the choice value that owns the choice.
     */
    public function choiceValue(): BelongsTo
    {
        return $this->belongsTo(ChoiceValue::class, 'choice_values_id');
    }
}