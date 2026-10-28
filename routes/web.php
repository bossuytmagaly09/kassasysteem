<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Pos\Terminal;
use App\Livewire\Scanner\CardManager;
use App\Models\CashierSession;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Beheer en Kassa's vergrendelen bij terugkeer naar het Hoofdmenu en alle geopende bestellingen wissen
    session()->forget('admin_pin_authenticated');

    foreach (['1', '2', '3'] as $kId) {
        $sessId = session("pos_cashier_session_id_{$kId}");
        if ($sessId) {
            try {
                CashierSession::where('id', $sessId)->whereNull('logged_out_at')->update(['logged_out_at' => now()]);
            } catch (Throwable) {
                // Ignore if database/tables are not initialized (e.g. in basic smoke tests)
            }
        }
        session()->forget([
            "pos_cashier_{$kId}",
            "pos_cashier_session_id_{$kId}",
        ]);
        Terminal::resetCustomerDisplay($kId);
    }

    return view('dashboard');
})->name('dashboard');

// Kassa afsluiten, open bestelling annuleren en vergrendelen
Route::get('/pos-exit', function () {
    $kassaId = (string) (request('kassa') ?: session('pos_kassa_id', '1'));
    $sessionId = session("pos_cashier_session_id_{$kassaId}");
    if ($sessionId) {
        CashierSession::where('id', $sessionId)->whereNull('logged_out_at')->update(['logged_out_at' => now()]);
    }
    session()->forget([
        "pos_cashier_{$kassaId}",
        "pos_cashier_session_id_{$kassaId}",
    ]);
    Terminal::resetCustomerDisplay($kassaId);

    return redirect()->route('dashboard');
})->name('pos.exit');

// Wisselen tussen Kassa 1 en Kassa 2 (wist open bestelling van de oude kassa)
Route::get('/pos-switch/{toKassa}', function ($toKassa) {
    $fromKassa = (string) (request('from') ?: session('pos_kassa_id', '1'));
    Terminal::resetCustomerDisplay($fromKassa);
    Terminal::resetCustomerDisplay((string) $toKassa);
    session(['pos_kassa_id' => (string) $toKassa]);

    return redirect()->route('pos.index', ['kassa' => $toKassa]);
})->name('pos.switch');

// Beacon / fetch om geopende bestelling direct te wissen bij verlaten scherm
Route::match(['get', 'post'], '/pos-clear-active-order', function () {
    $kassaId = (string) (request('kassa') ?: session('pos_kassa_id', '1'));
    Terminal::resetCustomerDisplay($kassaId);

    return response()->noContent();
})->name('pos.clear-active-order');

// Breeze Profile (still needs auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// POS Terminal (optioneel met kassa parameter: /pos of /pos/{kassa})
Route::get('/pos/{kassa?}', Terminal::class)->name('pos.index');

// 2de Scherm / Klantenscherm (Customer Display - Geen login vereist)
use App\Livewire\Pos\CustomerDisplay;
use App\Livewire\Pos\PriceBoard;

Route::get('/klantenscherm/{kassa?}', CustomerDisplay::class)->name('pos.display');
Route::get('/display/{kassa?}', CustomerDisplay::class);
Route::get('/prijzen', PriceBoard::class)->name('pos.priceboard');
Route::get('/prijsbord', PriceBoard::class);

use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\EventManager;
use App\Livewire\Admin\ImportManager;
use App\Livewire\Admin\PinLogin;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SalesLog;

Route::get('/scanner', CardManager::class)->name('scanner.index');

// Admin PIN Login & Vergrendeling
Route::get('/admin/pin', PinLogin::class)->name('admin.pin');
Route::get('/admin/lock', function () {
    session()->forget('admin_pin_authenticated');

    return redirect()->route('admin.pin');
})->name('admin.lock');
Route::get('/admin/exit', function () {
    session()->forget('admin_pin_authenticated');

    return redirect()->route('dashboard');
})->name('admin.exit');

// Beheer - Beveiligd met pincode / badge
Route::middleware(['admin.pin'])->prefix('admin')->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');
    Route::get('/verkopen', SalesLog::class)->name('admin.sales');
    Route::get('/verkoop-log', SalesLog::class)->name('admin.sales-log');
    Route::get('/producten', ProductManager::class)->name('admin.products');
    Route::get('/categorieen', CategoryManager::class)->name('admin.categories');
    Route::get('/events', EventManager::class)->name('admin.events');
    Route::get('/import', ImportManager::class)->name('admin.import');
});

require __DIR__.'/auth.php';
