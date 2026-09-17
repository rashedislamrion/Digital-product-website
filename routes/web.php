<?php

use App\Http\Controllers\CustomerOrderAccessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Customer order portal & guest passive account password setting
Route::get('/customer/orders', [CustomerOrderAccessController::class, 'orders'])->name('customer.orders');
Route::get('/customer/set-password', [CustomerOrderAccessController::class, 'showSetPassword'])->name('customer.set-password');
Route::post('/customer/set-password', [CustomerOrderAccessController::class, 'storeSetPassword'])->name('customer.set-password.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
