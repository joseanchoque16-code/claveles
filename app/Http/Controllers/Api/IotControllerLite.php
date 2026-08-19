<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\ControlRegla;
use App\Models\Dispositivo;
use App\Models\Lectura;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IotControllerLite extends Controller
{
    // ESP32 -> Laravel: sube lecturas
    public function storeLecturas(Request $request)
    {
        $data = $request->validate([
            'ts' => ['nullable', 'date'],
            'lecturas' => ['required', 'array', 'min:1'],
            'lecturas.*.codigo' => ['required', 'string'],
            'lecturas.*.valor' => ['required', 'numeric'],
        ]);

        $ts = $data['ts'] ?? now();
        $guardadas = 0;
        $ignoradas = [];

        DB::transaction(function () use ($data, $ts, &$guardadas, &$ignoradas) {
            foreach ($data['lecturas'] as $l) {
                $sensor = Sensor::where('codigo', $l['codigo'])->first();

                if (!$sensor) {
                    $ignoradas[] = $l['codigo'] . ':no_existe';
                    continue;
                }

                if (!$sensor->activo) {
                    $ignoradas[] = $l['codigo'] . ':inactivo';
                    continue;
                }

                $valor = (float) $l['valor'];

                Lectura::create([
                    'sensor_id' => $sensor->id,
                    'valor' => $valor,
                    'registrado_en' => $ts,
                ]);

                $sensor->valor_actual = $valor;
                $sensor->save();

                $this->alertasInstantaneas($sensor, $valor);
                $guardadas++;
            }
        });

        return response()->json([
            'ok' => true,
            'guardadas' => $guardadas,
            'ignoradas' => $ignoradas,
        ]);
    }

    // Laravel -> ESP32: baja config + estados deseados + sensores + reglas
    public function sync()
    {
        $conf = DB::table('configuracion_automatica')->first();

        $dispositivos = Dispositivo::where('habilitado', true)
            ->orderBy('id')
            ->get([
                'id',
                'codigo',
                'nombre',
                'tipo',
                'gpio_pin',
                'invertido',
                'estado',
                'habilitado',
            ]);

        $sensores = Sensor::orderBy('id')->get([
            'id',
            'codigo',
            'nombre',
            'tipo',
            'unidad',
            'gpio_pin',
            'activo',
            'valor_actual',
        ]);

        $reglas = ControlRegla::with(['sensor:id,codigo', 'dispositivo:id,codigo'])
            ->where('activa', true)
            ->orderBy('prioridad')
            ->orderBy('id')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'sensor_id' => $r->sensor_id,
                    'dispositivo_id' => $r->dispositivo_id,
                    'sensor_codigo' => optional($r->sensor)->codigo,
                    'dispositivo_codigo' => optional($r->dispositivo)->codigo,
                    'umbral_on' => $r->umbral_on,
                    'umbral_off' => $r->umbral_off,
                    'min_on_s' => $r->min_on_s,
                    'min_off_s' => $r->min_off_s,
                    'hora_inicio' => $r->hora_inicio,
                    'hora_fin' => $r->hora_fin,
                    'dias_semana' => $r->dias_semana,
                    'prioridad' => $r->prioridad,
                    'activa' => $r->activa,
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'config' => [
                'modo_global' => $conf->modo_global ?? 'automatico',
                'stale_min' => $conf->stale_min ?? 10,
                'timezone' => $conf->timezone ?? 'America/La_Paz',
                'config_version' => (int) ($conf->config_version ?? 1),
            ],
            'sensores' => $sensores,
            'dispositivos' => $dispositivos,
            'control_reglas' => $reglas,
        ]);
    }

    private function alertasInstantaneas(Sensor $sensor, float $valor): void
    {
        if ($sensor->codigo === 'S_NIVEL') {
            if ($valor <= 0) {
                $this->crearAlerta(
                    'TANK_EMPTY',
                    'critical',
                    $sensor->id,
                    null,
                    'Tanque vacío (0%).',
                    ['nivel' => $valor]
                );
            } elseif ($valor < 25) {
                $this->crearAlerta(
                    'TANK_EMPTY',
                    'warning',
                    $sensor->id,
                    null,
                    "Nivel bajo ({$valor}%).",
                    ['nivel' => $valor, 'min_riego' => 25]
                );
            }
        }

        if ($sensor->codigo === 'S_TEMP') {
            if ($valor > 28) {
                $this->crearAlerta(
                    'OUT_OF_RANGE',
                    'critical',
                    $sensor->id,
                    null,
                    "Temperatura alta: {$valor}°C",
                    ['temp' => $valor]
                );
            }

            if ($valor < 5) {
                $this->crearAlerta(
                    'OUT_OF_RANGE',
                    'critical',
                    $sensor->id,
                    null,
                    "Temperatura baja: {$valor}°C",
                    ['temp' => $valor]
                );
            }
        }

        if ($sensor->codigo === 'S_HR' && $valor > 85) {
            $this->crearAlerta(
                'OUT_OF_RANGE',
                'warning',
                $sensor->id,
                null,
                "Humedad relativa alta: {$valor}%",
                ['hr' => $valor]
            );
        }
    }

    private function crearAlerta(
        string $tipo,
        string $nivel,
        ?int $sensorId,
        ?int $dispositivoId,
        string $mensaje,
        array $contexto = []
    ): void {
        Alerta::firstOrCreate(
            [
                'tipo' => $tipo,
                'nivel' => $nivel,
                'sensor_id' => $sensorId,
                'dispositivo_id' => $dispositivoId,
                'visto' => false,
            ],
            [
                'mensaje' => $mensaje,
                'contexto' => $contexto,
            ]
        );
    }
}