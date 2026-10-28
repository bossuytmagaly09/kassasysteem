<?php

namespace App\Livewire\Pos;

use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dranken- & Prijzenbord')]
class PriceBoard extends Component
{
    public string $eventName = '';

    public int $vakPrice = 100;

    public int $cardPrice = 2000;

    public array $eventProducts = [];

    public function mount(): void
    {
        $this->loadEventData();
    }

    public function loadEventData(): void
    {
        $event = Event::active();
        if (! $event) {
            $this->eventName = 'Kassasysteem';
            $this->vakPrice = 100;
            $this->cardPrice = 2000;
            $this->eventProducts = [];

            return;
        }

        $this->eventName = $event->name;
        $this->vakPrice = $event->vak_price ?: 100;
        $this->cardPrice = $event->card_price ?: 2000;

        $this->eventProducts = $event->products()
            ->where('is_active', true)
            ->where('kind', '!=', 'punch_card')
            ->orderBy('category_id')
            ->orderBy('sort')
            ->get()
            ->map(function ($p): array {
                $price = (int) $p->pivot->price;
                $vCost = $this->vakPrice > 0 ? ($price / $this->vakPrice) : 0;
                $vText = (floor($vCost) == $vCost)
                    ? ((int) $vCost.' '.((int) $vCost === 1 ? 'vakje' : 'vakjes'))
                    : (number_format($vCost, 1, ',', '.').' vakjes');

                return [
                    'id' => $p->id,
                    'name' => preg_replace('/\s*\(.*?\)/', '', $p->name),
                    'price' => $price,
                    'vakjes_text' => $vText,
                    'sold_out' => (bool) $p->sold_out,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.pos.price-board')->layout('layouts.display');
    }
}
