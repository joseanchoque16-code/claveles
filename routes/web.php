<?php

use App\Http\Controllers\PanelController;
use App\Http\Controllers\DispositivoManualController;
use App\Http\Controllers\ControlReglaController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DispositivoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\ActuadorController;

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| PÁGINA PRINCIPAL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('panel')
        : view('welcome');
})->name('welcome');


/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PERFIL
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | PANEL
    |--------------------------------------------------------------------------
    */

    Route::get('/panel', [PanelController::class, 'index'])
        ->name('panel');

    Route::get('/panel/data', [PanelController::class, 'data'])
        ->name('panel.data');


    /*
    |--------------------------------------------------------------------------
    | LECTURAS
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::get('/lecturas', [LecturaController::class, 'index'])
        ->middleware('role:admin,operador')
        ->name('lecturas.index');

    Route::get('/lecturas/data', [LecturaController::class, 'data'])
        ->middleware('role:admin,operador')
        ->name('lecturas.data');


    /*
    |--------------------------------------------------------------------------
    | SENSORES
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::get('/sensores', [SensorController::class, 'index'])
        ->middleware('role:admin,operador')
        ->name('sensores.index');


    /*
    |--------------------------------------------------------------------------
    | ACTUADORES
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::get('/actuadores', [ActuadorController::class, 'index'])
        ->middleware('role:admin,operador')
        ->name('actuadores.index');


    /*
    |--------------------------------------------------------------------------
    | CONTROL MANUAL ON/OFF
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/dispositivos/{dispositivo}/manual',
        [DispositivoManualController::class, 'setEstado']
    )
        ->middleware('role:admin,operador')
        ->name('dispositivos.manual');


    /*
    |--------------------------------------------------------------------------
    | ALERTAS
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::get('/alertas', [AlertaController::class, 'index'])
        ->middleware('role:admin,operador')
        ->name('alertas.index');

    Route::post('/alertas/{alerta}/visto', [AlertaController::class, 'marcarVisto'])
        ->middleware('role:admin,operador')
        ->name('alertas.visto');


    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN - SOLO LECTURA
    | Admin u operador
    |--------------------------------------------------------------------------
    */

    Route::get('/config', [ConfiguracionController::class, 'index'])
        ->middleware('role:admin,operador')
        ->name('config.index');


    /*
    |--------------------------------------------------------------------------
    | SOLO ADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | REGLAS DE CONTROL
        |--------------------------------------------------------------------------
        */

        Route::resource('/control-reglas', ControlReglaController::class)
            ->only(['index', 'edit', 'update']);


        /*
        |--------------------------------------------------------------------------
        | DISPOSITIVOS
        |--------------------------------------------------------------------------
        |
        | Genera automáticamente:
        |
        | dispositivos.index
        | dispositivos.edit
        | dispositivos.update
        |
        */

        Route::resource('/dispositivos', DispositivoController::class)
            ->only(['index', 'edit', 'update']);


        /*
        |--------------------------------------------------------------------------
        | CONFIGURACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/config/edit', [ConfiguracionController::class, 'edit'])
            ->name('config.edit');

        Route::put('/config', [ConfiguracionController::class, 'update'])
            ->name('config.update');


        /*
        |--------------------------------------------------------------------------
        | SENSORES - ADMINISTRACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/sensores/{sensor}/edit', [SensorController::class, 'edit'])
            ->name('sensores.edit');

        Route::put('/sensores/{sensor}', [SensorController::class, 'update'])
            ->name('sensores.update');

        Route::post('/sensores/{sensor}/toggle', [SensorController::class, 'toggle'])
            ->name('sensores.toggle');


        /*
        |--------------------------------------------------------------------------
        | ACTUADORES - ADMINISTRACIÓN
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | Estas rutas usan actuadores.edit y actuadores.update
        | para no colisionar con dispositivos.edit y dispositivos.update.
        |
        */

        Route::get(
            '/actuadores/{dispositivo}/edit',
            [DispositivoController::class, 'edit']
        )->name('actuadores.edit');

        Route::put(
            '/actuadores/{dispositivo}',
            [DispositivoController::class, 'update']
        )->name('actuadores.update');
    });
});