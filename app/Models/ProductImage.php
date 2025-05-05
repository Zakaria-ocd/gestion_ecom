<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $guarded = ['id'];
    protected $fillable = ['product_id', 'image_url'];
}
