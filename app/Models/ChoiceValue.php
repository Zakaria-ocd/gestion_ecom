<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoiceValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['price', 'quantity'];

    /**
     * The relationships that should be eager loaded by default.
     *
     * @var array
     */
    protected $with = ['typeValues'];

    /**
     * Get the type values associated with this choice value
     *
     * @return BelongsToMany
     */
    public function typeValues(): BelongsToMany
{
        return $this->belongsToMany(
            TypeValue::class, 
            'type_value_choice_value', 
            'choice_value_id', 
            'type_value_id'
        )->withPivot('colorCode');
}

    /**
     * Get the choices that belong to this choice value
     *
     * @return HasMany
     */
    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class, 'choice_values_id');
    }
}