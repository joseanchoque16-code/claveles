<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Actuacion;
use App\Models\ControlRegla;
use App\Models\Dispositivo;
use App\Models\Lectura;
use App\Models\Sensor;
use App\Models\IotCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IotControllerLite extends Controller
{
    // ESP32 -> Laravel: sube lecturas
    public function storeLecturas(Request $request)
    {
        $data = $request->validate([
            'ts' => ['nullable', 'date'],
            'lecturas' => ['sometimes', 'array'],
            'lecturas.*.codigo' => ['required_with:lecturas', 'string', 'max:50'],
            'lecturas.*.valor' => ['required_with:lecturas', 'numeric'],
            'actuadores' => ['sometimes', 'array'],
            'actuadores.*.codigo' => ['required_with:actuadores', 'string', 'max:50'],
            'actuadores.*.estado' => ['required_with:actuadores', 'integer', 'in:0,1'],
            'actuadores.*.origen' => ['nullable', 'in:manual,automatico'],
        ]);

        if (empty($data['lecturas']) && empty($data['actuadores'])) {
            return response()->json(['message' => 'Envía al menos una lectura o un estado de actuador.'], 422);
        }

        $ts = $data['ts'] ?? now();
        $guardadas = 0;
        $actuadoresActualizados = 0;
        $ignoradas = [];

        DB::transaction(function () use ($data, $ts, &$guardadas, &$actuadoresActualizados, &$ignoradas) {
            foreach (($data['lecturas'] ?? []) as $l) {
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

            foreach (($data['actuadores'] ?? []) as $item) {
                $device = Dispositivo::where('codigo', $item['codigo'])->first();

                if (!$device || !$device->habilitado) {
                    $ignoradas[] = $item['codigo'] . ':actuador_no_disponible';
                    continue;
                }

                $estado = (int) $item['estado'];
                if ((int) $device->estado === $estado) {
                    continue;
                }

                $device->estado = $estado;
                $device->save();

                Actuacion::create([
                    'dispositivo_id' => $device->id,
                    'control_regla_id' => null,
                    'accion' => $estado === 1 ? 'on' : 'off',
                    'origen' => $item['origen'] ?? 'automatico',
                    'valor_sensor' => null,
                    'motivo' => ['source' => 'esp32_telemetry'],
                    'ejecutado_en' => $ts,
                ]);
                $actuadoresActualizados++;
            }
        });

        return response()->json([
            'ok' => true,
            'guardadas' => $guardadas,
            'actuadores_actualizados' => $actuadoresActualizados,
            'ignoradas' => $ignoradas,
        ]);
    }

    // Laravel -> ESP32: baja config + estados deseados + sensores + reglas
    public function sync()
    {
        $commandsEnabled = (bool) config('app.iot_commands_enabled');
        if ($commandsEnabled) {
            IotCommand::where('status', 'pending')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired', 'updated_at' => now()]);
        }

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

        $commands = collect();
        if ($commandsEnabled) {
            $commands = IotCommand::with('dispositivo:id,codigo')
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('requested_at')
                ->get()
                ->map(fn (IotCommand $command) => [
                    'command_id' => $command->command_id,
                    'dispositivo_id' => $command->dispositivo_id,
                    'dispositivo_codigo' => $command->dispositivo?->codigo,
                    'accion' => $command->accion,
                    'estado' => $command->accion === 'on' ? 1 : 0,
                    'requested_at' => $command->requested_at?->toIso8601String(),
                    'expires_at' => $command->expires_at?->toIso8601String(),
                ])
                ->values();
        }

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
            'commands' => $commands,
        ]);
    }

    // ESP32 -> Laravel: confirmación idempotente de ejecución/rechazo/fallo.
    public function acknowledgeCommand(Request $request)
    {
        if (!config('app.iot_commands_enabled')) {
            return response()->json(['ok' => false, 'message' => 'El protocolo ACK aún no está habilitado.'], 503);
        }

        $data = $request->validate([
            'command_id' => ['required', 'uuid'],
            'resultado' => ['required', 'in:executed,rejected,failed'],
            'mensaje' => ['nullable', 'string', 'max:500'],
            'estado_actual' => ['required_if:resultado,executed', 'nullable', 'integer', 'in:0,1'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $command = IotCommand::where('command_id', $data['command_id'])
                ->lockForUpdate()
                ->first();

            if (!$command) {
                return ['http' => 404, 'body' => ['ok' => false, 'message' => 'Comando no encontrado.']];
            }

            if ($command->status !== 'pending') {
                return ['http' => 200, 'body' => [
                    'ok' => true,
                    'duplicate' => true,
                    'command_id' => $command->command_id,
                    'status' => $command->status,
                ]];
            }

            if ($command->expires_at->isPast()) {
                $command->update(['status' => 'expired']);
                return ['http' => 409, 'body' => ['ok' => false, 'status' => 'expired', 'command_id' => $command->command_id]];
            }

            $device = Dispositivo::whereKey($command->dispositivo_id)->lockForUpdate()->first();
            $status = $data['resultado'];
            $actualState = array_key_exists('estado_actual', $data) ? (int) $data['estado_actual'] : null;
            $requestedState = $command->accion === 'on' ? 1 : 0;

            if ($status === 'executed' && $actualState !== null && $actualState !== $requestedState) {
                $status = 'failed';
            }

            $command->update([
                'status' => $status,
                'resultado' => [
                    'mensaje' => $data['mensaje'] ?? null,
                    'estado_actual' => $actualState,
                ],
                'acknowledged_at' => now(),
            ]);

            if (in_array($status, ['rejected', 'failed'], true)) {
                $this->crearAlerta(
                    'COMMAND_FAILED',
                    $status === 'failed' ? 'critical' : 'warning',
                    null,
                    $command->dispositivo_id,
                    $data['mensaje'] ?? "El ESP32 reportó la orden {$command->command_id} como {$status}.",
                    ['command_id' => $command->command_id, 'accion' => $command->accion, 'status' => $status]
                );
            }

            if ($status === 'executed' && $device) {
                $device->update(['estado' => $requestedState]);
                Actuacion::create([
                    'dispositivo_id' => $device->id,
                    'control_regla_id' => null,
                    'accion' => $command->accion,
                    'origen' => 'manual',
                    'valor_sensor' => null,
                    'motivo' => ['command_id' => $command->command_id, 'by_user_id' => $command->requested_by],
                    'ejecutado_en' => now(),
                ]);
            }

            return ['http' => 200, 'body' => [
                'ok' => true,
                'command_id' => $command->command_id,
                'status' => $status,
                'estado_confirmado' => $status === 'executed' ? $requestedState : null,
            ]];
        });

        return response()->json($result['body'], $result['http']);
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
            if ($valor >= 24) {
                $this->crearAlerta(
                    'OUT_OF_RANGE',
                    'critical',
                    $sensor->id,
                    null,
                    "Temperatura alta fuera del rango documentado: {$valor}°C",
                    ['temp' => $valor]
                );
            }

            if ($valor < 10) {
                $this->crearAlerta(
                    'OUT_OF_RANGE',
                    $valor <= 5 ? 'critical' : 'warning',
                    $sensor->id,
                    null,
                    "Temperatura baja respecto al mínimo nocturno documentado: {$valor}°C",
                    ['temp' => $valor]
                );
            }
        }

        if ($sensor->codigo === 'S_HR' && $valor >= 75) {
            $this->crearAlerta(
                'OUT_OF_RANGE',
                'warning',
                $sensor->id,
                null,
                "Humedad relativa alta; ventilación requerida: {$valor}%",
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
