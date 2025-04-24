<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    
    protected $guarded = ['id'];

    public $timestamps = false;

    public function categories()
    {
        return $this->belongsTo(Category::class);
    }
}