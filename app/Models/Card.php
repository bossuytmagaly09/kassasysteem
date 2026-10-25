<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = [
        'poef_id',
        'hangaar_user_id',
        'drank_user_id',
        'uid',
        'card_hash',
        'card_type',
        'label',
        'balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hangaar_user_id' => 'integer',
            'drank_user_id' => 'integer',
            'balance' => 'integer',
        ];
    }

    public function poef(): BelongsTo
    {
        return $this->belongsTo(Poef::class);
    }
}
