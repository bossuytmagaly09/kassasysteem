<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Event;
use App\Models\Product;
use App\PaymentMode;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class EventManager extends Component
{
    public ?int $selectedEventId = null;

    public bool $showEventModal = false;

    public ?int $editingEventId = null;

    public string $name = '';

    public ?string $date = null;

    public string $paymentMode = 'beide';

    /** Vak price in euro, as typed in the form. */
    public string $vakPrice = '1.00';

    /** Card purchase price in euro, as typed in the form. */
    public string $cardPrice = '20.00';

    public bool $eventJustCreated = false;

    /** Mode for adding items: 'new' (setup custom drink/food) or 'existing' (pick from catalog) */
    public string $addMode = 'new';

    /** Base product details */
    public string $productName = '';

    public ?int $categoryId = null;

    public bool $showNewCategoryInput = false;

    public string $newCategoryName = '';

    /**
     * Packaging & purchase unit: 'fles', 'blik', 'vat', 'stuk'
     */
    public string $purchaseUnit = 'fles';

    public string $purchaseQuantity = '';

    /** Purchase price per packaging unit in euro (e.g. per bottle, can, keg, piece) */
    public string $purchasePrice = '';

    /** Package volume and unit (e.g. '1', '1.5', '0.75', '50', '33') */
    public string $packageVolume = '1';

    public string $packageVolumeUnit = 'L'; // 'L', 'cl'

    /** Serving volume in cl (e.g. '20', '25', '33', '12.5', '4') */
    public string $servingVolume = '20';

    /** Bottle selling options */
    public string $bottleSellMode = 'glas'; // 'glas', 'fles', 'beide'

    public string $glassesPerBottle = '5';

    public string $glassPrice = '';

    public string $bottlePrice = '';

    /** Can selling options */
    public string $canPrice = '';

    /** Keg selling options */
    public string $glassesPerKeg = '150';

    public string $kegGlassPrice = '';

    /** Piece / Snack selling options */
    public string $itemPrice = '';

    /** Form fields for adding an existing catalog product */
    public ?int $newProductId = null;

    public string $newPrice = '';

    public string $newStock = '';

    /** Copy products from another event */
    public bool $showCopyModal = false;

    public ?int $copyFromEventId = null;

    /** @var array<int, string> */
    public array $productPrices = [];

    /** @var array<int, string> */
    public array $productPurchasePrices = [];

    /** @var array<int, string> */
    public array $productStocks = [];

    public string $feedbackMessage = '';

    public function mount(): void
    {
        $this->ensureDefaultCategories();
        $this->categoryId = Category::query()->value('id');
        $this->selectedEventId = Event::active()?->id ?? Event::query()->latest()->value('id');
        $this->syncSelectedEventPrices();
        $this->loadProductRows();
    }

    public function selectEvent(int $eventId): void
    {
        $this->selectedEventId = $eventId;
        $this->eventJustCreated = false;
        $this->feedbackMessage = '';
        $this->syncSelectedEventPrices();
        $this->resetNewProductForm();
        $this->loadProductRows();
    }

    private function syncSelectedEventPrices(): void
    {
        if ($this->selectedEventId) {
            $event = Event::find($this->selectedEventId);
            if ($event) {
                $this->vakPrice = number_format($event->vak_price / 100, 2, '.', '');
                $this->cardPrice = $event->card_price ? number_format($event->card_price / 100, 2, '.', '') : '20.00';
            }
        }
    }

    public function updateEventPrices(): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        $validated = $this->validate([
            'vakPrice' => ['required', 'numeric', 'min:0.01'],
            'cardPrice' => ['required', 'numeric', 'min:0.50'],
        ], [
            'vakPrice.required' => 'Vul een waarde per vakje in.',
            'cardPrice.required' => 'Vul een drankkaartprijs in.',
        ]);

        $event = Event::findOrFail($this->selectedEventId);
        $event->update([
            'vak_price' => (int) round(((float) str_replace(',', '.', $validated['vakPrice'])) * 100),
            'card_price' => (int) round(((float) str_replace(',', '.', $validated['cardPrice'])) * 100),
        ]);

        $this->feedbackMessage = 'Prijzen voor drankkaart (€ '.number_format($event->card_price / 100, 2, ',', '.').') en vakje (€ '.number_format($event->vak_price / 100, 2, ',', '.').') zijn succesvol bijgewerkt!';
    }

    public function createEvent(): void
    {
        $this->resetValidation();
        $this->editingEventId = null;
        $this->name = '';
        $this->date = now()->toDateString();
        $this->paymentMode = PaymentMode::Beide->value;
        $this->vakPrice = '1.00';
        $this->cardPrice = '20.00';
        $this->showEventModal = true;
    }

    public function editEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        $this->resetValidation();
        $this->editingEventId = $event->id;
        $this->name = $event->name;
        $this->date = $event->date?->toDateString();
        $this->paymentMode = $event->payment_mode->value;
        $this->vakPrice = number_format($event->vak_price / 100, 2, '.', '');
        $this->cardPrice = $event->card_price ? number_format($event->card_price / 100, 2, '.', '') : '20.00';
        $this->showEventModal = true;
    }

    public function saveEvent(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'paymentMode' => ['required', Rule::enum(PaymentMode::class)],
            'vakPrice' => ['required', 'numeric', 'min:0.01'],
            'cardPrice' => ['nullable', 'numeric', 'min:0.50'],
        ], [
            'name.required' => 'Vul een naam in voor het event.',
            'vakPrice.required' => 'Vul een prijs per vakje in.',
        ]);

        $isNew = ($this->editingEventId === null);

        $event = Event::updateOrCreate(['id' => $this->editingEventId], [
            'name' => $validated['name'],
            'date' => $validated['date'],
            'payment_mode' => $validated['paymentMode'],
            'vak_price' => (int) round(((float) str_replace(',', '.', $validated['vakPrice'])) * 100),
            'card_price' => ! empty($validated['cardPrice']) ? (int) round(((float) str_replace(',', '.', $validated['cardPrice'])) * 100) : 2000,
        ]);

        $this->showEventModal = false;
        $this->selectEvent($event->id);

        if ($isNew) {
            $this->eventJustCreated = true;
        }
    }

    public function activateEvent(int $eventId): void
    {
        Event::findOrFail($eventId)->activate();
    }

    public function deactivateEvent(int $eventId): void
    {
        Event::whereKey($eventId)->update(['is_active' => false]);
    }

    public function deleteEvent(int $eventId): void
    {
        Event::whereKey($eventId)->delete();

        if ($this->selectedEventId === $eventId) {
            $this->selectedEventId = Event::query()->latest()->value('id');
            $this->loadProductRows();
        }
    }

    public function setAddMode(string $mode): void
    {
        $this->addMode = $mode;
        $this->resetValidation();
    }

    public function setPurchaseUnit(string $unit): void
    {
        $this->purchaseUnit = $unit;
        if ($unit === 'fles') {
            $this->packageVolume = '1';
            $this->packageVolumeUnit = 'L';
            $this->servingVolume = '20';
            $this->recalculateGlassesFromVolume();
        } elseif ($unit === 'blik') {
            $this->packageVolume = '33';
            $this->packageVolumeUnit = 'cl';
            $this->servingVolume = '';
        } elseif ($unit === 'vat') {
            $this->packageVolume = '50';
            $this->packageVolumeUnit = 'L';
            $this->servingVolume = '25';
            $this->recalculateGlassesFromVolume();
        } else {
            $this->packageVolume = '';
            $this->packageVolumeUnit = 'stuk';
            $this->servingVolume = '';
        }
        $this->resetValidation();
    }

    public function setVolumePreset(string $volume, string $unit = 'L'): void
    {
        $this->packageVolume = $volume;
        $this->packageVolumeUnit = $unit;
        $this->recalculateGlassesFromVolume();
    }

    public function setServingPreset(string $cl): void
    {
        $this->servingVolume = $cl;
        $this->recalculateGlassesFromVolume();
    }

    public function updatedPackageVolume(): void
    {
        $this->recalculateGlassesFromVolume();
    }

    public function updatedPackageVolumeUnit(): void
    {
        $this->recalculateGlassesFromVolume();
    }

    public function updatedServingVolume(): void
    {
        $this->recalculateGlassesFromVolume();
    }

    public function recalculateGlassesFromVolume(): void
    {
        $vol = (float) str_replace(',', '.', $this->packageVolume);
        $serving = (float) str_replace(',', '.', $this->servingVolume);

        if ($vol <= 0 || $serving <= 0) {
            return;
        }

        // Convert package volume to centiliters
        $totalCl = $this->packageVolumeUnit === 'L' ? ($vol * 100) : $vol;

        // Calculate portions
        $portions = (int) floor($totalCl / $serving);

        if ($portions >= 1) {
            if ($this->purchaseUnit === 'fles') {
                $this->glassesPerBottle = (string) $portions;
            } elseif ($this->purchaseUnit === 'vat') {
                $this->glassesPerKeg = (string) $portions;
            }
        }
    }

    public function toggleNewCategory(): void
    {
        $this->showNewCategoryInput = ! $this->showNewCategoryInput;
        $this->newCategoryName = '';
    }

    /**
     * Add a drink or food item with packaging & serving conversion to the event.
     */
    public function addNewProduct(): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        $event = Event::findOrFail($this->selectedEventId);

        $rules = [
            'productName' => ['required', 'string', 'max:255'],
            'purchaseUnit' => ['required', Rule::in(['fles', 'blik', 'vat', 'stuk'])],
            'purchaseQuantity' => ['nullable', 'integer', 'min:0'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($this->showNewCategoryInput) {
            $rules['newCategoryName'] = ['required', 'string', 'max:100'];
        } else {
            $rules['categoryId'] = ['required', 'exists:categories,id'];
        }

        if ($this->purchaseUnit === 'fles') {
            $rules['bottleSellMode'] = ['required', Rule::in(['glas', 'fles', 'beide'])];
            if (in_array($this->bottleSellMode, ['glas', 'beide'], true)) {
                $rules['glassesPerBottle'] = ['required', 'integer', 'min:1'];
                $rules['glassPrice'] = ['required', 'numeric', 'min:0'];
            }
            if (in_array($this->bottleSellMode, ['fles', 'beide'], true)) {
                $rules['bottlePrice'] = ['required', 'numeric', 'min:0'];
            }
        } elseif ($this->purchaseUnit === 'blik') {
            $rules['canPrice'] = ['required', 'numeric', 'min:0'];
        } elseif ($this->purchaseUnit === 'vat') {
            $rules['glassesPerKeg'] = ['required', 'integer', 'min:1'];
            $rules['kegGlassPrice'] = ['required', 'numeric', 'min:0'];
        } elseif ($this->purchaseUnit === 'stuk') {
            $rules['itemPrice'] = ['required', 'numeric', 'min:0'];
        }

        $this->validate($rules, [
            'productName.required' => 'Vul een naam in voor de drank of het eten.',
            'categoryId.required' => 'Kies een categorie.',
            'newCategoryName.required' => 'Vul de naam van de nieuwe categorie in.',
            'glassPrice.required' => 'Vul de verkoopprijs per glas in.',
            'bottlePrice.required' => 'Vul de verkoopprijs per fles in.',
            'canPrice.required' => 'Vul de verkoopprijs per blik in.',
            'kegGlassPrice.required' => 'Vul de verkoopprijs per glas in.',
            'itemPrice.required' => 'Vul de verkoopprijs in.',
        ]);

        if ($this->showNewCategoryInput) {
            $catName = trim($this->newCategoryName);
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName]
            );
            $this->categoryId = $category->id;
            $this->showNewCategoryInput = false;
            $this->newCategoryName = '';
        } else {
            $category = Category::findOrFail($this->categoryId);
        }

        $qtyBought = ($this->purchaseQuantity === '' || $this->purchaseQuantity === null) ? null : (int) $this->purchaseQuantity;
        $purchasePriceInCents = ($this->purchasePrice === '' || $this->purchasePrice === null) ? null : (int) round((float) $this->purchasePrice * 100);
        $baseName = trim($this->productName);
        $packageVolumeString = trim($this->packageVolume) !== '' ? (trim($this->packageVolume).' '.$this->packageVolumeUnit) : null;
        $servingVolumeString = trim($this->servingVolume) !== '' ? (trim($this->servingVolume).' cl') : null;

        /** @var array<int, array{name: string, price: int, stock_planned: ?int, purchase_unit: string, purchase_quantity: ?int, purchase_price: ?int, package_volume: ?string, servings_per_unit: int, serving_unit: string, serving_volume: ?string}> $itemsToAttach */
        $itemsToAttach = [];

        if ($this->purchaseUnit === 'fles') {
            $glPerBottle = max((int) $this->glassesPerBottle, 1);
            if (in_array($this->bottleSellMode, ['glas', 'beide'], true)) {
                $stockPlanned = $qtyBought === null ? null : $qtyBought * $glPerBottle;
                $itemsToAttach[] = [
                    'name' => $this->bottleSellMode === 'beide' ? $baseName.' Glas' : $baseName,
                    'price' => (int) round((float) $this->glassPrice * 100),
                    'stock_planned' => $stockPlanned,
                    'purchase_unit' => 'fles',
                    'purchase_quantity' => $qtyBought,
                    'purchase_price' => $purchasePriceInCents,
                    'package_volume' => $packageVolumeString,
                    'servings_per_unit' => $glPerBottle,
                    'serving_unit' => 'glas',
                    'serving_volume' => $servingVolumeString,
                ];
            }
            if (in_array($this->bottleSellMode, ['fles', 'beide'], true)) {
                $itemsToAttach[] = [
                    'name' => $this->bottleSellMode === 'beide' ? $baseName.' Fles' : $baseName,
                    'price' => (int) round((float) $this->bottlePrice * 100),
                    'stock_planned' => $qtyBought,
                    'purchase_unit' => 'fles',
                    'purchase_quantity' => $qtyBought,
                    'purchase_price' => $purchasePriceInCents,
                    'package_volume' => $packageVolumeString,
                    'servings_per_unit' => 1,
                    'serving_unit' => 'fles',
                    'serving_volume' => $packageVolumeString,
                ];
            }
        } elseif ($this->purchaseUnit === 'blik') {
            $itemsToAttach[] = [
                'name' => $baseName,
                'price' => (int) round((float) $this->canPrice * 100),
                'stock_planned' => $qtyBought,
                'purchase_unit' => 'blik',
                'purchase_quantity' => $qtyBought,
                'purchase_price' => $purchasePriceInCents,
                'package_volume' => $packageVolumeString,
                'servings_per_unit' => 1,
                'serving_unit' => 'blik',
                'serving_volume' => $packageVolumeString,
            ];
        } elseif ($this->purchaseUnit === 'vat') {
            $glPerKeg = max((int) $this->glassesPerKeg, 1);
            $itemsToAttach[] = [
                'name' => $baseName,
                'price' => (int) round((float) $this->kegGlassPrice * 100),
                'stock_planned' => $qtyBought === null ? null : $qtyBought * $glPerKeg,
                'purchase_unit' => 'vat',
                'purchase_quantity' => $qtyBought,
                'purchase_price' => $purchasePriceInCents,
                'package_volume' => $packageVolumeString,
                'servings_per_unit' => $glPerKeg,
                'serving_unit' => 'glas',
                'serving_volume' => $servingVolumeString,
            ];
        } else { // 'stuk'
            $itemsToAttach[] = [
                'name' => $baseName,
                'price' => (int) round((float) $this->itemPrice * 100),
                'stock_planned' => $qtyBought,
                'purchase_unit' => 'stuk',
                'purchase_quantity' => $qtyBought,
                'purchase_price' => $purchasePriceInCents,
                'package_volume' => null,
                'servings_per_unit' => 1,
                'serving_unit' => 'stuk',
                'serving_volume' => null,
            ];
        }

        foreach ($itemsToAttach as $item) {
            $product = Product::firstOrCreate(
                ['name' => $item['name'], 'category_id' => $category->id],
                ['price' => $item['price'], 'is_active' => true]
            );

            if ($event->products()->where('products.id', $product->id)->exists()) {
                $event->products()->updateExistingPivot($product->id, [
                    'price' => $item['price'],
                    'stock_planned' => $item['stock_planned'],
                    'purchase_unit' => $item['purchase_unit'],
                    'purchase_quantity' => $item['purchase_quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'package_volume' => $item['package_volume'],
                    'servings_per_unit' => $item['servings_per_unit'],
                    'serving_unit' => $item['serving_unit'],
                    'serving_volume' => $item['serving_volume'],
                ]);
            } else {
                $event->products()->attach($product->id, [
                    'price' => $item['price'],
                    'stock_planned' => $item['stock_planned'],
                    'purchase_unit' => $item['purchase_unit'],
                    'purchase_quantity' => $item['purchase_quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'package_volume' => $item['package_volume'],
                    'servings_per_unit' => $item['servings_per_unit'],
                    'serving_unit' => $item['serving_unit'],
                    'serving_volume' => $item['serving_volume'],
                ]);
            }
        }

        $this->feedbackMessage = count($itemsToAttach) > 1
            ? "'{$baseName}' toegevoegd als glas én als fles aan dit event!"
            : "'{$itemsToAttach[0]['name']}' succesvol toegevoegd aan dit event!";

        // Reset inputs
        $this->productName = '';
        $this->purchaseQuantity = '';
        $this->purchasePrice = '';
        $this->glassPrice = '';
        $this->bottlePrice = '';
        $this->canPrice = '';
        $this->kegGlassPrice = '';
        $this->itemPrice = '';
        $this->resetValidation();
        $this->loadProductRows();
    }

    /**
     * Add an existing catalog product to the event.
     */
    public function addExistingProduct(): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        $event = Event::findOrFail($this->selectedEventId);

        $validated = $this->validate([
            'newProductId' => ['required', 'exists:products,id', Rule::notIn($event->products()->pluck('products.id'))],
            'newPrice' => ['required', 'numeric', 'min:0'],
            'newStock' => ['nullable', 'integer', 'min:0'],
        ], [
            'newProductId.required' => 'Kies een product uit de lijst.',
            'newProductId.not_in' => 'Dit product staat al in het event.',
            'newPrice.required' => 'Vul een verkoopprijs in.',
        ]);

        $product = Product::findOrFail($validated['newProductId']);

        $event->products()->attach($validated['newProductId'], [
            'price' => (int) round($validated['newPrice'] * 100),
            'stock_planned' => $validated['newStock'] === '' || $validated['newStock'] === null ? null : (int) $validated['newStock'],
            'purchase_unit' => 'stuk',
            'purchase_quantity' => $validated['newStock'] === '' || $validated['newStock'] === null ? null : (int) $validated['newStock'],
            'servings_per_unit' => 1,
            'serving_unit' => 'stuk',
        ]);

        $this->feedbackMessage = "'{$product->name}' toegevoegd aan dit event!";
        $this->resetNewProductForm();
        $this->loadProductRows();
    }

    public function updatedNewProductId(): void
    {
        $product = $this->newProductId ? Product::find($this->newProductId) : null;
        $this->newPrice = $product ? number_format($product->price / 100, 2, '.', '') : '';
    }

    public function updateProduct(int $productId): void
    {
        $this->validate([
            "productPrices.$productId" => ['required', 'numeric', 'min:0'],
            "productPurchasePrices.$productId" => ['nullable', 'numeric', 'min:0'],
            "productStocks.$productId" => ['nullable', 'integer', 'min:0'],
        ]);

        $stock = $this->productStocks[$productId] ?? '';
        $pPrice = $this->productPurchasePrices[$productId] ?? '';

        Event::findOrFail($this->selectedEventId)->products()->updateExistingPivot($productId, [
            'price' => (int) round((float) $this->productPrices[$productId] * 100),
            'purchase_price' => ($pPrice === '' || $pPrice === null) ? null : (int) round((float) $pPrice * 100),
            'stock_planned' => $stock === '' ? null : (int) $stock,
        ]);

        $this->dispatch('product-saved', productId: $productId);
    }

    public function removeProduct(int $productId): void
    {
        Event::findOrFail($this->selectedEventId)->products()->detach($productId);
        $this->loadProductRows();
    }

    public function copyFromEvent(): void
    {
        if (! $this->copyFromEventId || ! $this->selectedEventId) {
            return;
        }

        $sourceEvent = Event::with('products')->findOrFail($this->copyFromEventId);
        $targetEvent = Event::findOrFail($this->selectedEventId);

        $count = 0;
        foreach ($sourceEvent->products as $product) {
            if (! $targetEvent->products()->where('products.id', $product->id)->exists()) {
                $targetEvent->products()->attach($product->id, [
                    'price' => $product->pivot->price,
                    'stock_planned' => $product->pivot->stock_planned,
                    'purchase_unit' => $product->pivot->purchase_unit,
                    'purchase_quantity' => $product->pivot->purchase_quantity,
                    'purchase_price' => $product->pivot->purchase_price,
                    'package_volume' => $product->pivot->package_volume,
                    'servings_per_unit' => $product->pivot->servings_per_unit,
                    'serving_unit' => $product->pivot->serving_unit,
                    'serving_volume' => $product->pivot->serving_volume,
                ]);
                $count++;
            }
        }

        $this->showCopyModal = false;
        $this->copyFromEventId = null;
        $this->feedbackMessage = "{$count} producten gekopieerd van event '{$sourceEvent->name}'!";
        $this->loadProductRows();
    }

    private function resetNewProductForm(): void
    {
        $this->productName = '';
        $this->purchaseQuantity = '';
        $this->purchasePrice = '';
        $this->glassPrice = '';
        $this->bottlePrice = '';
        $this->canPrice = '';
        $this->kegGlassPrice = '';
        $this->itemPrice = '';
        $this->newProductId = null;
        $this->newPrice = '';
        $this->newStock = '';
        $this->showNewCategoryInput = false;
        $this->newCategoryName = '';
        $this->resetValidation();
    }

    private function loadProductRows(): void
    {
        $this->productPrices = [];
        $this->productPurchasePrices = [];
        $this->productStocks = [];

        $event = $this->selectedEventId ? Event::find($this->selectedEventId) : null;

        foreach ($event?->products ?? [] as $product) {
            $this->productPrices[$product->id] = number_format($product->pivot->price / 100, 2, '.', '');
            $this->productPurchasePrices[$product->id] = $product->pivot->purchase_price === null ? '' : number_format($product->pivot->purchase_price / 100, 2, '.', '');
            $this->productStocks[$product->id] = $product->pivot->stock_planned === null ? '' : (string) $product->pivot->stock_planned;
        }
    }

    private function ensureDefaultCategories(): void
    {
        if (Category::query()->count() === 0) {
            $defaults = [
                'Frisdrank' => 'frisdrank',
                'Bieren' => 'bieren',
                'Wijnen & Bubbels' => 'wijnen-bubbels',
                'Cocktails & Sterk' => 'cocktails-sterk',
                'Eten & Snacks' => 'eten-snacks',
                'Warme Dranken' => 'warme-dranken',
            ];
            foreach ($defaults as $name => $slug) {
                Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
            }
        }
    }

    public function render(): View
    {
        $selectedEvent = $this->selectedEventId
            ? Event::with(['products.category'])->find($this->selectedEventId)
            : null;

        $sold = $selectedEvent?->soldQuantities() ?? collect();

        return view('livewire.admin.event-manager', [
            'events' => Event::query()->orderByDesc('is_active')->orderByDesc('date')->get(),
            'selectedEvent' => $selectedEvent,
            'sold' => $sold,
            'revenue' => $selectedEvent?->orders()->sum('total_amount') ?? 0,
            'categories' => Category::query()->orderBy('name')->get(),
            'availableProducts' => Product::query()
                ->with('category')
                ->whereNotIn('id', $selectedEvent?->products->pluck('id') ?? [])
                ->orderBy('name')
                ->get(),
            'otherEvents' => Event::query()
                ->where('id', '!=', $this->selectedEventId)
                ->orderByDesc('date')
                ->get(),
            'paymentModes' => PaymentMode::cases(),
        ])->layout('layouts.admin');
    }
}
