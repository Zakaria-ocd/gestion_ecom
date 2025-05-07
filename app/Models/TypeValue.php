<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeValue extends Model
{
    use HasFactory;

    protected $fillable = ['value', 'type_id'];
    public function type()
    {
        return $this->belongsTo(Type::class);
    }
    public function choiceValues()
    {
        return $this->belongsToMany(ChoiceValue::class, 'type_value_choice_value');
    }
}