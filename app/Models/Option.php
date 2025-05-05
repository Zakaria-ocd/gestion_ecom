<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $guarded = ['id'];
    public function optionValue()
    {
        return $this->hasMany(OptionValue::class);
    }
}
