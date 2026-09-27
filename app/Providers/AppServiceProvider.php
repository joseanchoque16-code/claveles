<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Sensor;
use App\Models\Alerta;
use App\Models\ConfiguracionAutomatica;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
        public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            $role = Auth::check() ? (Auth::user()->role ?? 'lector') : 'lector';

            // Temperatura (S_TEMP) normalizada
            $tempRaw = Sensor::where('codigo', 'S_TEMP')->value('valor_actual');
            $temp = null;
            if (!is_null($tempRaw)) {
                $t = str_replace(',', '.', trim((string) $tempRaw));
                $temp = is_numeric($t) ? (float) $t : null;
            }

            // Modo global (manual/automatico)
            $modo = ConfiguracionAutomatica::modoGlobal();

            // Alertas no vistas (solo admin/operador)
            $alertasNoVistas = in_array($role, ['admin','operador'])
                ? Alerta::where('visto', false)->count()
                : 0;

            $roleLabel = match ($role) {
                'admin' => 'Administrador',
                'operador' => 'Operador',
                default => 'Lector',
            };

            $view->with(compact('temp', 'modo', 'alertasNoVistas', 'roleLabel', 'role'));
        });
    }

}
