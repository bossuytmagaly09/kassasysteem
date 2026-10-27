<?php

namespace App\Models;

use App\PaymentMode;
use App\Services\EventSettlementService;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'date',
        'payment_mode',
        'vak_price',
        'card_price',
        'punch_value',
        'is_active',
        'is_closed',
        'closed_at',
        'poef_settled',
        'poef_settled_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'payment_mode' => PaymentMode::class,
            'vak_price' => 'integer',
            'card_price' => 'integer',
            'punch_value' => 'decimal:2',
            'is_active' => 'boolean',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
            'poef_settled' => 'boolean',
            'poef_settled_at' => 'datetime',
        ];
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->where('is_closed', false)->first();
    }

    public function activate(): void
    {
        DB::transaction(function (): void {
            static::query()->whereKeyNot($this->id)->update(['is_active' => false]);
            $this->update(['is_active' => true, 'is_closed' => false]);
        });
    }

    public function close(): array
    {
        return app(EventSettlementService::class)->closeAndSettle($this);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot([
                'price',
                'stock_planned',
                'purchase_unit',
                'purchase_quantity',
                'purchase_price',
                'package_volume',
                'servings_per_unit',
                'serving_unit',
                'serving_volume',
            ])
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Sold quantity per product id for this event.
     *
     * @return Collection<int, int>
     */
    public function soldQuantities(): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.event_id', $this->id)
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sold')
            ->pluck('sold', 'product_id')
            ->map(fn ($sold): int => (int) $sold);
    }
}
