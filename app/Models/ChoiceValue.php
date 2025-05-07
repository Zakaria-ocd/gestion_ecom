<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChoiceValue extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'quantity'];
    public function typeValues()
    {
        return $this->belongsToMany(TypeValue::class, 'type_value_choice_value');
    }
    public function choices()
    {
        return $this->hasMany(Choice::class, 'choice_values_id');
    }
}