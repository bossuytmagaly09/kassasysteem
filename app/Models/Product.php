<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'external_id',
        'category_id',
        'name',
        'kind',
        'description',
        'price',
        'punch_cost',
        'punches',
        'stock',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'punch_cost' => 'integer',
            'punches' => 'integer',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
