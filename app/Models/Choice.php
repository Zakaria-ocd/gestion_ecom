<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'choice_values_id'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function choiceValue()
    {
        return $this->belongsTo(ChoiceValue::class, 'choice_values_id');
    }
}