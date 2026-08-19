<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Dispositivo;
use App\Models\Alerta;
use App\Models\Actuacion;
use App\Models\ControlRegla;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role ?? 'lector';

        $modo = DB::table('configuracion_automatica')->value('modo_global') ?? 'automatico';

        $roleLabel = match($role) {
            'admin' => 'Administrador',
            'operador' => 'Operador',
            default => 'Lector',
        };

        // Sensores activos
        $sensores = Sensor::where('activo', true)
            ->orderBy('id')
            ->get();

        // Temperatura para cabecera
        $temp = Sensor::where('codigo', 'S_TEMP')->value('valor_actual');

        // Solo admin/operador
        $dispositivos = collect();
        $alertas = collect();
        $alertasNoVistas = 0;
        $eventos = collect();
        $reglasResumen = collect();

        if (in_array($role, ['admin', 'operador'])) {
            $dispositivos = Dispositivo::orderBy('id')->get();

            $alertas = Alerta::orderByDesc('created_at')
                ->limit(5)
                ->get();

            $alertasNoVistas = Alerta::where('visto', false)->count();

            $eventos = Actuacion::with('dispositivo')
                ->orderByDesc('ejecutado_en')
                ->limit(5)
                ->get();

            $reglas = ControlRegla::with(['sensor', 'dispositivo'])
                ->where('activa', true)
                ->orderBy('prioridad')
                ->get();

            $reglasResumen = $this->mapearReglasResumen($reglas);
        }

        // Resumen superior
        $resumen = [
            'sensores_activos'      => Sensor::where('activo', true)->count(),
            'actuadores_encendidos' => Dispositivo::where('estado', 1)->count(),
            'alertas_criticas'      => Alerta::where('nivel', 'critical')->count(),
            'alertas_no_vistas'     => $alertasNoVistas,
            'modo'                  => $modo,
            'nivel_agua'            => optional(
                Sensor::where('codigo', 'S_NIVEL')->first()
            )->valor_actual,
        ];

        return view('panel', compact(
            'sensores',
            'dispositivos',
            'alertas',
            'temp',
            'modo',
            'alertasNoVistas',
            'roleLabel',
            'resumen',
            'eventos',
            'reglasResumen',
            'role'
        ));
    }

    public function data()
    {
        $user = Auth::user();
        $role = $user->role ?? 'lector';

        $modo = DB::table('configuracion_automatica')->value('modo_global') ?? 'automatico';

        $sensores = Sensor::where('activo', true)
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre', 'tipo', 'unidad', 'valor_actual', 'activo', 'updated_at']);

        $payload = [
            'modo' => $modo,
            'sensores' => $sensores,
            'ts' => now()->toDateTimeString(),
            'resumen' => [
                'sensores_activos'      => Sensor::where('activo', true)->count(),
                'actuadores_encendidos' => Dispositivo::where('estado', 1)->count(),
                'alertas_criticas'      => Alerta::where('nivel', 'critical')->count(),
                'alertas_no_vistas'     => Alerta::where('visto', false)->count(),
                'nivel_agua'            => optional(
                    Sensor::where('codigo', 'S_NIVEL')->first()
                )->valor_actual,
            ],
        ];

        if (in_array($role, ['admin', 'operador'])) {
            $payload['alertasNoVistas'] = Alerta::where('visto', false)->count();

            $payload['dispositivos'] = Dispositivo::orderBy('id')
                ->get(['id', 'codigo', 'nombre', 'estado', 'habilitado', 'updated_at']);
        }

        return response()->json($payload);
    }

    private function mapearReglasResumen($reglas)
    {
        $resumen = collect();

        foreach ($reglas as $r) {
            $sensorCodigo = $r->sensor->codigo ?? null;
            $dispositivoCodigo = $r->dispositivo->codigo ?? null;

            $texto = null;

            if ($dispositivoCodigo === 'D_FAN' && $sensorCodigo === 'S_TEMP') {
                $texto = "Ventilador: enciende ≥ {$r->umbral_on} °C, apaga ≤ {$r->umbral_off} °C";
            } elseif ($dispositivoCodigo === 'D_FAN' && $sensorCodigo === 'S_HR') {
                $texto = "Ventilador por humedad: enciende ≥ {$r->umbral_on} %, apaga ≤ {$r->umbral_off} %";
            } elseif ($dispositivoCodigo === 'D_LUZ' && $sensorCodigo === 'S_LUZ') {
                $horario = ($r->hora_inicio && $r->hora_fin)
                    ? " | horario {$r->hora_inicio} - {$r->hora_fin}"
                    : "";
                $texto = "Iluminación: enciende ≤ {$r->umbral_on} %, apaga ≥ {$r->umbral_off} %{$horario}";
            } elseif ($dispositivoCodigo === 'D_RIEGO' && $sensorCodigo === 'S_HSUELO') {
                $texto = "Riego: enciende ≤ {$r->umbral_on} %, apaga ≥ {$r->umbral_off} %";
            } elseif ($dispositivoCodigo === 'D_CALEF' && $sensorCodigo === 'S_TEMP') {
                $texto = "Calefacción: enciende ≤ {$r->umbral_on} °C, apaga ≥ {$r->umbral_off} °C";
            }

            if ($texto) {
                $resumen->push([
                    'dispositivo_id' => $r->dispositivo_id,
                    'dispositivo_codigo' => $dispositivoCodigo,
                    'sensor_codigo' => $sensorCodigo,
                    'texto' => $texto,
                    'umbral_on' => $r->umbral_on,
                    'umbral_off' => $r->umbral_off,
                    'min_on_s' => $r->min_on_s,
                    'min_off_s' => $r->min_off_s,
                    'hora_inicio' => $r->hora_inicio,
                    'hora_fin' => $r->hora_fin,
                ]);
            }
        }

        return $resumen;
    }
}