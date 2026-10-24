<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Livewire\Component;

class Dashboard extends Component
{
    public $revenueKaart = 0;

    public $revenueBende = 0;

    public $totalRevenue = 0;

    public function mount(): void
    {
        $today = today();

        $this->revenueKaart = Order::whereDate('created_at', $today)
            ->where('payment_method', 'drankkaart')
            ->sum('total_amount');

        $this->revenueBende = Order::whereDate('created_at', $today)
            ->where('payment_method', 'scanner')
            ->sum('total_amount');

        $this->totalRevenue = Order::whereDate('created_at', $today)->sum('total_amount');
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('layouts.admin');
    }
}
