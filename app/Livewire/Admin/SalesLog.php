<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SalesLog extends Component
{
    use WithPagination;

    public ?int $selectedEventId = null;

    public string $paymentMethodFilter = '';

    public string $search = '';

    public string $activeTab = 'items';

    public function mount(?int $eventId = null): void
    {
        $this->selectedEventId = $eventId ?? Event::active()?->id ?? Event::query()->latest()->value('id');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedEventId(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        $events = Event::query()->latest()->get();
        $selectedEvent = $this->selectedEventId ? Event::find($this->selectedEventId) : null;

        $ordersBase = Order::query()
            ->when($this->selectedEventId, fn ($q) => $q->where('event_id', $this->selectedEventId))
            ->when($this->paymentMethodFilter !== '', fn ($q) => $q->where('payment_method', $this->paymentMethodFilter));

        $totalRevenue = (int) (clone $ordersBase)->sum('total_amount');
        $ordersCount = (int) (clone $ordersBase)->count();

        $cashRevenue = (int) (clone $ordersBase)->where('payment_method', 'cash')->sum('total_amount');
        $cashCount = (int) (clone $ordersBase)->where('payment_method', 'cash')->count();

        $qrRevenue = (int) (clone $ordersBase)->where('payment_method', 'qr')->sum('total_amount');
        $qrCount = (int) (clone $ordersBase)->where('payment_method', 'qr')->count();

        $scannerRevenue = (int) (clone $ordersBase)->where('payment_method', 'scanner')->sum('total_amount');
        $scannerCount = (int) (clone $ordersBase)->where('payment_method', 'scanner')->count();

        $drankkaartCount = (int) (clone $ordersBase)->where('payment_method', 'drankkaart')->count();
        $drankkaartVakjes = (int) (clone $ordersBase)->where('payment_method', 'drankkaart')->sum('vakjes');

        $drankkaartenSold = (int) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($this->selectedEventId, fn ($q) => $q->where('orders.event_id', $this->selectedEventId))
            ->where('order_items.product_name', 'Drankkaart')
            ->sum('order_items.quantity');

        $drankkaartenRevenue = (int) (OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($this->selectedEventId, fn ($q) => $q->where('orders.event_id', $this->selectedEventId))
            ->where('order_items.product_name', 'Drankkaart')
            ->selectRaw('SUM(order_items.quantity * order_items.product_price) as total')
            ->value('total') ?? 0);

        $productSales = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($this->selectedEventId, fn ($q) => $q->where('orders.event_id', $this->selectedEventId))
            ->when($this->paymentMethodFilter !== '', fn ($q) => $q->where('orders.payment_method', $this->paymentMethodFilter))
            ->when($this->search !== '', fn ($q) => $q->where('order_items.product_name', 'like', "%{$this->search}%"))
            ->groupBy('order_items.product_name', 'order_items.product_price', 'categories.name')
            ->selectRaw('
                order_items.product_name,
                order_items.product_price,
                COALESCE(categories.name, "Drankkaarten & Extra") as category_name,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.quantity * order_items.product_price) as total_amount
            ')
            ->orderByDesc('total_quantity')
            ->get();

        $orders = Order::query()
            ->with(['items', 'event', 'poef'])
            ->when($this->selectedEventId, fn ($q) => $q->where('event_id', $this->selectedEventId))
            ->when($this->paymentMethodFilter !== '', fn ($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('uuid', 'like', $term)
                        ->orWhereHas('poef', fn ($p) => $p->where('name', 'like', $term)->orWhere('poef_name', 'like', $term))
                        ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(25);

        return view('livewire.admin.sales-log', [
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'totalRevenue' => $totalRevenue,
            'ordersCount' => $ordersCount,
            'cashRevenue' => $cashRevenue,
            'cashCount' => $cashCount,
            'qrRevenue' => $qrRevenue,
            'qrCount' => $qrCount,
            'scannerRevenue' => $scannerRevenue,
            'scannerCount' => $scannerCount,
            'drankkaartCount' => $drankkaartCount,
            'drankkaartVakjes' => $drankkaartVakjes,
            'drankkaartenSold' => $drankkaartenSold,
            'drankkaartenRevenue' => $drankkaartenRevenue,
            'productSales' => $productSales,
            'orders' => $orders,
        ])->layout('layouts.admin');
    }
}
