<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['cart_id', 'product_id', 'choice_value_id', 'quantity', 'price'];
    
    public $timestamps = false;

    /**
     * Get the cart that owns the item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product associated with the cart item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the choice value associated with the cart item.
     */
    public function choiceValue()
    {
        return $this->belongsTo(ChoiceValue::class);
    }
}
