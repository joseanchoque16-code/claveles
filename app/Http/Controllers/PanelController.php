<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Dispositivo;
use App\Models\Alerta;
use App\Models\Actuacion;
use App\Models\ControlRegla;
use App\Models\IotCommand;
use App\Models\ConfiguracionAutomatica;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role ?? 'lector';

        $modo = ConfiguracionAutomatica::modoGlobal();
        $esp32Status = $this->esp32Status();

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
        $pendingCommands = collect();

        if (in_array($role, ['admin', 'operador'])) {
            $dispositivos = Dispositivo::orderBy('id')->get();
            if (config('app.iot_commands_enabled')) {
                $pendingCommands = IotCommand::where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('requested_at')
                    ->get(['dispositivo_id', 'accion', 'command_id'])
                    ->unique('dispositivo_id')
                    ->keyBy('dispositivo_id');
            }

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
            'role',
            'pendingCommands',
            'esp32Status'
        ));
    }

    public function data()
    {
        $user = Auth::user();
        $role = $user->role ?? 'lector';

        $modo = ConfiguracionAutomatica::modoGlobal();

        $sensores = Sensor::where('activo', true)
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre', 'tipo', 'unidad', 'valor_actual', 'activo', 'updated_at']);

        $payload = [
            'modo' => $modo,
            'esp32' => $this->esp32Status(),
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

            $pendingCommands = collect();
            if (config('app.iot_commands_enabled')) {
                $pendingCommands = IotCommand::where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('requested_at')
                    ->get(['dispositivo_id', 'accion', 'command_id'])
                    ->unique('dispositivo_id')
                    ->keyBy('dispositivo_id');
            }

            $payload['dispositivos'] = Dispositivo::orderBy('id')
                ->get(['id', 'codigo', 'nombre', 'estado', 'habilitado', 'updated_at']);

            $payload['dispositivos']->transform(function ($device) use ($pendingCommands) {
                $command = $pendingCommands->get($device->id);
                $device->pending = $command !== null;
                $device->pending_estado = $command?->accion === 'on' ? 1 : ($command ? 0 : null);
                return $device;
            });
        }

        return response()->json($payload);
    }

    private function esp32Status(): array
    {
        $lastSeen = Cache::get('iot.esp32.last_seen');
        if (!is_numeric($lastSeen)) {
            return ['state' => 'never', 'online' => false, 'last_seen' => null, 'seconds_ago' => null];
        }

        $lastSeen = (int) $lastSeen;
        $secondsAgo = max(0, now()->timestamp - $lastSeen);

        return [
            'state' => $secondsAgo <= 15 ? 'online' : 'offline',
            'online' => $secondsAgo <= 15,
            'last_seen' => $lastSeen,
            'seconds_ago' => $secondsAgo,
        ];
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
