<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'kassa_id',
        'cashier_name',
        'hangaar_user_id',
        'card_uid',
        'auth_method',
        'logged_in_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'hangaar_user_id' => 'integer',
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }
}
