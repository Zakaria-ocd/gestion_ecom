<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the values for the type.
     */
    public function values(): HasMany
    {
        return $this->hasMany(TypeValue::class);
    }
}