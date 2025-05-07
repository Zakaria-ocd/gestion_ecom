<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeValue extends Model
{
    use HasFactory;

    protected $fillable = ['value', 'type_id'];

    /**
     * Get the type that owns the value.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Get the choice values that belong to this type value.
     */
    public function choiceValues(): BelongsToMany
    {
        return $this->belongsToMany(ChoiceValue::class, 'type_value_choice_value');
    }
}