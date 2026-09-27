<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IotControllerLite;

Route::prefix('v1')->middleware('iot.key')->group(function () {
    Route::post('/lecturas', [IotControllerLite::class, 'storeLecturas']);
    Route::get('/esp32/sync', [IotControllerLite::class, 'sync']);
    Route::post('/commands/ack', [IotControllerLite::class, 'acknowledgeCommand']);
});

// Rutas documentadas para el protocolo IoT. Se conservan las rutas v1
// anteriores para mantener compatibilidad con firmware ya desplegado.
Route::prefix('iot/v1')->middleware('iot.key')->group(function () {
    Route::post('/lecturas', [IotControllerLite::class, 'storeLecturas']);
    Route::get('/sync', [IotControllerLite::class, 'sync']);
    Route::post('/ack', [IotControllerLite::class, 'acknowledgeCommand']);
});
