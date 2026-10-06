<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'payment_method',
        'payment_status',
        'delivery_status',
        'address',
        'city',
        'state',
        'postal_code',
        'phone',
        'notes',
        'recipient_name',
        'email',
    ];

    protected $appends = ['total_price', 'status'];

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->total_amount;
    }

    public function getStatusAttribute(): string
    {
        return $this->delivery_status;
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
