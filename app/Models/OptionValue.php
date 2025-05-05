<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    protected $guarded = ['id'];
    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
