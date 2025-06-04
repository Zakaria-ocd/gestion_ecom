<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'choice_value_id',
        'price',
    ];
    
    /**
     * The relationships that should be eager loaded by default.
     *
     * @var array
     */
    protected $with = ['product'];

    /**
     * No timestamps for this model
     */
    public $timestamps = false;

    /**
     * Get the cart that owns the item
     *
     * @return BelongsTo
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this cart item
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the choice value for this cart item
     *
     * @return BelongsTo
     */
    public function choiceValue(): BelongsTo
    {
        return $this->belongsTo(ChoiceValue::class);
    }
}
