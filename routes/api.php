<?php

use App\Http\Controllers\Api\LicenseApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider / Application within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('v1/licenses')
    ->middleware(['throttle:licenses'])
    ->group(function () {
        Route::post('/activate', [LicenseApiController::class, 'activate'])->name('api.v1.licenses.activate');
        Route::post('/validate', [LicenseApiController::class, 'validateLicense'])->name('api.v1.licenses.validate');
        Route::post('/deactivate', [LicenseApiController::class, 'deactivate'])->name('api.v1.licenses.deactivate');
    });
