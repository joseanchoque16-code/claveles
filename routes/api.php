<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IotControllerLite;

Route::prefix('v1')->middleware('iot.key')->group(function () {
    Route::post('/lecturas', [IotControllerLite::class, 'storeLecturas']);
    Route::get('/esp32/sync', [IotControllerLite::class, 'sync']);
});