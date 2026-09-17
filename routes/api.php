<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\RfcHistoryController;
use App\Http\Controllers\Api\V1\RfcStatusController;
use App\Http\Controllers\Api\V1\SatDatasetImportController;
use App\Http\Controllers\Api\V1\VendorRfcController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::middleware(['auth.organization', 'throttle:rfc-lookup'])->group(function () {
        Route::get('/rfcs/{rfc}/status', RfcStatusController::class);
        Route::get('/rfcs/{rfc}/history', RfcHistoryController::class);
        Route::get('/vendors/{zoho_vendor_id}/rfc', [VendorRfcController::class, 'show']);
        Route::put('/vendors/{zoho_vendor_id}/rfc', [VendorRfcController::class, 'store']);
    });

    Route::post('/datasets/sat/import', SatDatasetImportController::class)
        ->middleware('auth.sat-import');
});
