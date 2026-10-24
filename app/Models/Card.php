<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = [
        'poef_id',
        'uid',
        'card_type',
        'label',
        'balance',
        'status',
    ];

    public function poef(): BelongsTo
    {
        return $this->belongsTo(Poef::class);
    }
}
