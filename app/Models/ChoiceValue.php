<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoiceValue extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'quantity'];

    /**
     * Get the type values that belong to this choice value.
     */
    public function typeValues(): BelongsToMany
    {
        return $this->belongsToMany(TypeValue::class, 'type_value_choice_value');
    }

    /**
     * Get the choices that use this choice value.
     */
    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class, 'choice_values_id');
    }
}