<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $fillable = [
        'name', 
        'description', 
        'price', 
        'quantity', 
        'category_id', 
        'seller_id'
    ];

    public function productOptions()
    {
        return $this->hasMany(ProductOption::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}