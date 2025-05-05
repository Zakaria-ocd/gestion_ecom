<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'product_id', 
        'option_value_id',
        'price',
        'quantity'
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }
}
