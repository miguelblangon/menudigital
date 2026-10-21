<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ["category_id", "name", "description", "price", "image", "available"];

    protected $casts = [
        "available" => "boolean",
    ];

    public function setAvailableAttribute($value)
    {
        $this->attributes['is_available'] = (bool) $value;
    }

    public function getAvailableAttribute()
    {
        return (bool) ($this->attributes['is_available'] ?? true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class);
    }
}