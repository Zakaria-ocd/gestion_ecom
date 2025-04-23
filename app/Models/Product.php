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
        'price',
        'category_id',
        'seller_id',
    ];
    public $timestamps = false;

    public function categories()
    {
        return $this->belongsTo(Category::class);
    }
}
