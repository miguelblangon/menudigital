<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /**
     * La columna se llama `available` (antes `is_available`). Hay un único nombre
     * válido, sin accessor/mutator que aliasse: así un typo al escribir falla en
     * los tests en lugar de quedarse silenciosamente en el valor por defecto.
     *
     * @var list<string>
     */
    protected $fillable = [
        "category_id",
        "name",
        "description",
        "price",
        "image",
        "available",
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "price" => "float",
            "available" => "boolean",
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class);
    }

    /**
     * Productos que se muestran en la carta pública.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where("available", true);
    }
}
