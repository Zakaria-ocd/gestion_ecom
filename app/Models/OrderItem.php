<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['order_id', 'product_id', 'choice_value_id', 'quantity', 'price'];
    
    public $timestamps = false;

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this order item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the choice value associated with the order item.
     */
    public function choiceValue()
    {
        return $this->belongsTo(ChoiceValue::class);
    }
} 