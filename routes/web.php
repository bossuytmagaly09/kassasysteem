<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Pos\Terminal;
use App\Livewire\Scanner\CardManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

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

Route::get('/klantenscherm/{kassa?}', CustomerDisplay::class)->name('pos.display');
Route::get('/display/{kassa?}', CustomerDisplay::class);

use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\EventManager;
use App\Livewire\Admin\ImportManager;
use App\Livewire\Admin\PinLogin;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SalesLog;

// Admin PIN Login
Route::get('/admin/pin', PinLogin::class)->name('admin.pin');
Route::get('/admin/lock', function () {
    session()->forget('admin_pin_authenticated');

    return redirect()->route('admin.pin');
})->name('admin.lock');

// Beheer & Kaarten - Beveiligd met admin PIN
Route::middleware(['admin.pin'])->group(function () {
    Route::get('/scanner', CardManager::class)->name('scanner.index');

    Route::prefix('admin')->group(function () {
        Route::get('/', Dashboard::class)->name('admin.dashboard');
        Route::get('/verkopen', SalesLog::class)->name('admin.sales');
        Route::get('/verkoop-log', SalesLog::class)->name('admin.sales-log');
        Route::get('/producten', ProductManager::class)->name('admin.products');
        Route::get('/categorieen', CategoryManager::class)->name('admin.categories');
        Route::get('/events', EventManager::class)->name('admin.events');
        Route::get('/import', ImportManager::class)->name('admin.import');
    });
});

require __DIR__.'/auth.php';
