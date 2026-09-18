<?php

use App\Http\Controllers\CustomerOrderAccessController;
use App\Http\Controllers\Payment\SslcommerzCallbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\DownloadController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderConfirmationController;
use App\Http\Controllers\Storefront\ProductCatalogController;
use Illuminate\Support\Facades\Route;

// Public Storefront Catalog
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [ProductCatalogController::class, 'search'])->name('search');
Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])->name('products.show');
Route::post('/products/{product}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('products.reviews.store');
Route::get('/categories/{slug}', [ProductCatalogController::class, 'category'])->name('categories.show');

// Shopping Cart
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/cart/data', [CartController::class, 'data'])->name('cart.data');

// Single-Page Checkout
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'process'])
    ->middleware('throttle:checkout')
    ->name('checkout.process');
Route::get('/checkout/payment-failed', [CheckoutController::class, 'failed'])->name('checkout.payment-failed');

// Order Confirmation
Route::get('/orders/{order_number}/confirmation', [OrderConfirmationController::class, 'show'])->name('orders.confirmation');

// Secure Digital Delivery Pipeline (@research.md 5.1)
Route::get('/library/download/{download_grant}', [DownloadController::class, 'download'])
    ->name('library.download');

// Local driver secure binary serving endpoint (for pre-signed dev/testing URLs)
Route::get('/files/secure-serve/{path}', function (\Illuminate\Http\Request $request, string $path) {
    if (! $request->hasValidSignature()) {
        abort(403, 'Invalid or expired download signature.');
    }

    return response('Digital delivery package binary stream', 200, [
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'attachment; filename="'.basename($path).'"',
        'Cache-Control' => 'private, no-store',
    ]);
})->where('path', '.*')->name('files.secure_serve');

// SSLCOMMERZ Payment Callbacks & IPN
Route::post('/payment/sslcommerz/success', [SslcommerzCallbackController::class, 'success'])->name('payment.sslcommerz.success');
Route::post('/payment/sslcommerz/fail', [SslcommerzCallbackController::class, 'fail'])->name('payment.sslcommerz.fail');
Route::post('/payment/sslcommerz/cancel', [SslcommerzCallbackController::class, 'cancel'])->name('payment.sslcommerz.cancel');
Route::post('/payment/sslcommerz/ipn', [SslcommerzCallbackController::class, 'ipn'])->name('payment.sslcommerz.ipn');

// Paddle Payment Webhooks (@research.md §6.2)
Route::post('/payment/paddle/webhook', \App\Http\Controllers\Payment\PaddleWebhookController::class)->name('payment.paddle.webhook');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Customer digital library & self-service (@research.md §3.5 & §7.5)
Route::get('/library', [CustomerOrderAccessController::class, 'library'])->name('library');
Route::get('/customer/library', [CustomerOrderAccessController::class, 'library'])->name('customer.library');
Route::get('/customer/orders', [CustomerOrderAccessController::class, 'orders'])->name('customer.orders');
Route::get('/customer/orders/{order}/invoice', [CustomerOrderAccessController::class, 'downloadInvoice'])->name('customer.orders.invoice');
Route::post('/customer/deactivate/{activation}', [CustomerOrderAccessController::class, 'deactivate'])->name('customer.deactivate');
Route::post('/customer/support-tickets', [CustomerOrderAccessController::class, 'storeSupportTicket'])->name('customer.support-tickets.store');
Route::get('/customer/set-password', [CustomerOrderAccessController::class, 'showSetPassword'])->name('customer.set-password');
Route::post('/customer/set-password', [CustomerOrderAccessController::class, 'storeSetPassword'])->name('customer.set-password.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
