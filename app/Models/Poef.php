<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poef extends Model
{
    protected $fillable = [
        'external_id',
        'hangaar_user_id',
        'drank_user_id',
        'name',
        'poef_name',
        'has_poef',
        'discount_percentage',
    ];

    protected function casts(): array
    {
        return [
            'hangaar_user_id' => 'integer',
            'drank_user_id' => 'integer',
            'has_poef' => 'boolean',
            'discount_percentage' => 'integer',
        ];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders that have not been settled to the external database yet.
     */
    public function openOrders(): HasMany
    {
        return $this->hasMany(Order::class)->whereNull('settled_at');
    }
}
