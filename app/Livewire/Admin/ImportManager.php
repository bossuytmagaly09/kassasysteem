<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class ImportManager extends Component
{
    public $logs = [];

    public $isSyncing = false;

    public $productCount = 0;

    public function sync()
    {
        $this->isSyncing = true;
        $this->logs[] = '['.now()->format('H:i:s').'] Import gestart...';

        try {
            Artisan::call('pos:sync-products');
            $output = Artisan::output();

            $this->logs[] = '['.now()->format('H:i:s').'] Verbinding met dranksite succesvol.';
            $this->logs[] = '['.now()->format('H:i:s').'] Categorieën gesynchroniseerd.';
            $this->logs[] = '['.now()->format('H:i:s').'] Producten en prijzen geüpdatet.';
            $this->logs[] = '['.now()->format('H:i:s').'] '.$output;

            $this->productCount = Product::count();
            $this->logs[] = '['.now()->format('H:i:s').'] Totaal actieve producten nu in kassa: '.$this->productCount;
            $this->logs[] = '['.now()->format('H:i:s').'] Import succesvol afgerond.';

        } catch (\Exception $e) {
            $this->logs[] = '['.now()->format('H:i:s').'] FOUT: Kon niet verbinden met dranksite database. Controleer de .env configuratie.';
        }

        $this->isSyncing = false;
    }

    public function render()
    {
        return view('livewire.admin.import-manager')->layout('layouts.admin');
    }
}
