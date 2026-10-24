<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poef extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'discount_percentage',
    ];

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
