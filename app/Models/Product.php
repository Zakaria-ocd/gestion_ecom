<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'category_id', 
        'seller_id'
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    public function choices()
    {
        return $this->hasMany(Choice::class);
    }
}