<?php

namespace App\Livewire\Admin;

use App\Models\Card;
use App\Models\Category;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use App\Services\BeheerApiService;
use Livewire\Component;

class ImportManager extends Component
{
    public array $logs = [];

    public bool $isSyncing = false;

    public bool $isPushingSales = false;

    public ?string $pingStatus = null;

    public int $productCount = 0;

    public int $categoryCount = 0;

    public int $peopleCount = 0;

    public int $cardsCount = 0;

    public int $pendingSalesCount = 0;

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $this->productCount = Product::count();
        $this->categoryCount = Category::count();
        $this->peopleCount = Poef::count();
        $this->cardsCount = Card::count();
        $this->pendingSalesCount = Order::where('beheer_status', 'pending')->whereNotNull('uuid')->count();
    }

    public function checkConnection(BeheerApiService $service): void
    {
        try {
            $result = $service->ping();
            $this->pingStatus = 'Verbonden met Beheer ('.$result['service'].') om '.($result['time'] ?? now()->toTimeString());
            $this->logs[] = '['.now()->format('H:i:s').'] ✓ Ping succesvol: '.$this->pingStatus;
        } catch (\Throwable $e) {
            $this->pingStatus = 'Fout: '.$e->getMessage();
            $this->logs[] = '['.now()->format('H:i:s').'] ✗ Verbinding mislukt: '.$e->getMessage();
        }
    }

    public function sync(BeheerApiService $service): void
    {
        $this->isSyncing = true;
        $this->logs[] = '['.now()->format('H:i:s').'] Synchronisatie snapshot van beheer.tjeute.be gestart...';

        try {
            $result = $service->syncSnapshot();
            foreach ($result['logs'] as $log) {
                $this->logs[] = '['.now()->format('H:i:s').'] ✓ '.$log;
            }

            $this->refreshStats();
            $this->logs[] = '['.now()->format('H:i:s').'] Snapshot succesvol bijgewerkt.';
        } catch (\Throwable $e) {
            $this->logs[] = '['.now()->format('H:i:s').'] FOUT: '.$e->getMessage().' (Controleer BEHEER_API_TOKEN in .env)';
        }

        $this->isSyncing = false;
    }

    public function pushSales(BeheerApiService $service): void
    {
        $this->isPushingSales = true;
        $this->logs[] = '['.now()->format('H:i:s').'] Doorsturen van openstaande verkopen gestart...';

        try {
            $result = $service->syncPendingSales();
            $this->logs[] = '['.now()->format('H:i:s').'] '.($result['message'] ?? 'Verwerkt');
            $this->refreshStats();
        } catch (\Throwable $e) {
            $this->logs[] = '['.now()->format('H:i:s').'] FOUT bij doorsturen: '.$e->getMessage();
        }

        $this->isPushingSales = false;
    }

    public function render()
    {
        return view('livewire.admin.import-manager')->layout('layouts.admin');
    }
}
