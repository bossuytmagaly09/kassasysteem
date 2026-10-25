<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'uuid',
        'event_id',
        'user_id',
        'poef_id',
        'card_id',
        'total_amount',
        'status',
        'payment_method',
        'card_uid',
        'card_hash',
        'device',
        'vakjes',
        'beheer_status',
        'beheer_sale_id',
        'beheer_error',
        'settled_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'vakjes' => 'integer',
            'beheer_sale_id' => 'integer',
            'settled_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function poef(): BelongsTo
    {
        return $this->belongsTo(Poef::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
