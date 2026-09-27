<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Actuacion;
use App\Models\Dispositivo;
use App\Models\IotCommand;
use App\Models\Sensor;
use App\Models\ConfiguracionAutomatica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispositivoManualController extends Controller
{
    public function setEstado(Request $request, Dispositivo $dispositivo)
    {
        $data = $request->validate([
            'estado' => ['required', 'integer', 'in:0,1'], // 0=OFF, 1=ON
        ]);

        if (!$dispositivo->habilitado) {
            return response()->json(['ok' => false, 'msg' => 'Dispositivo deshabilitado'], 422);
        }

        $nuevoEstado = (int) $data['estado'];
        $modo = ConfiguracionAutomatica::modoGlobal();

        if ($modo !== 'manual') {
            return response()->json([
                'ok' => false,
                'msg' => 'Cambia el sistema a modo manual antes de enviar órdenes directas.',
            ], 409);
        }

        // Seguridad: bloquear riego si nivel < 25%
        if ($dispositivo->codigo === 'D_RIEGO' && $nuevoEstado === 1) {
            $nivel = Sensor::where('codigo', 'S_NIVEL')->value('valor_actual'); // 0/25/50/75/100
            $nivel = is_null($nivel) ? 0 : (float)$nivel;

            if ($nivel < 25) {
                $this->crearAlerta(
                    tipo: 'IRRIGATION_BLOCKED',
                    nivel: $nivel <= 0 ? 'critical' : 'warning',
                    sensorCodigo: 'S_NIVEL',
                    dispositivoId: $dispositivo->id,
                    mensaje: "Riego bloqueado: nivel de agua insuficiente ({$nivel}%).",
                    contexto: ['nivel' => $nivel, 'min_permitido' => 25]
                );

                return response()->json([
                    'ok' => false,
                    'msg' => 'Riego bloqueado por nivel de agua < 25%',
                    'nivel' => $nivel
                ], 409);
            }
        }

        if ($nuevoEstado === 1 && in_array($dispositivo->codigo, ['D_FAN', 'D_CALEF'], true)) {
            $conflicto = Dispositivo::where('codigo', $dispositivo->codigo === 'D_FAN' ? 'D_CALEF' : 'D_FAN')
                ->where('estado', 1)
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'No se puede activar calefacción y ventilación al mismo tiempo.',
                ], 409);
            }
        }

        // Compatibilidad durante el despliegue gradual: el firmware anterior
        // toma el estado deseado desde sync, pero todavía no procesa ACK.
        if (!config('app.iot_commands_enabled')) {
            DB::transaction(function () use ($dispositivo, $nuevoEstado, $request) {
                $dispositivo->estado = $nuevoEstado;
                $dispositivo->save();

                Actuacion::create([
                    'dispositivo_id' => $dispositivo->id,
                    'control_regla_id' => null,
                    'accion' => $nuevoEstado === 1 ? 'on' : 'off',
                    'origen' => 'manual',
                    'valor_sensor' => null,
                    'motivo' => ['by_user_id' => $request->user()->id, 'compatibilidad_firmware' => true],
                    'ejecutado_en' => now(),
                ]);

                DB::table('configuracion_automatica')->increment('config_version');
            });

            return response()->json([
                'ok' => true,
                'status' => 'legacy_sync',
                'dispositivo' => [
                    'id' => $dispositivo->id,
                    'codigo' => $dispositivo->codigo,
                    'estado' => $nuevoEstado,
                ],
            ]);
        }

        $command = DB::transaction(function () use ($dispositivo, $nuevoEstado, $request) {
            // Una orden nueva invalida la anterior que aún no fue confirmada.
            IotCommand::where('dispositivo_id', $dispositivo->id)
                ->where('status', 'pending')
                ->update(['status' => 'superseded', 'updated_at' => now()]);

            return IotCommand::create([
                'command_id' => (string) Str::uuid(),
                'dispositivo_id' => $dispositivo->id,
                'requested_by' => $request->user()->id,
                'accion' => $nuevoEstado === 1 ? 'on' : 'off',
                'status' => 'pending',
                'requested_at' => now(),
                'expires_at' => now()->addMinutes(5),
            ]);
        });

        return response()->json([
            'ok' => true,
            'status' => 'pending',
            'message' => 'Orden enviada a la cola; el estado cambiará cuando el ESP32 confirme su ejecución.',
            'command_id' => $command->command_id,
            'expires_at' => $command->expires_at->toIso8601String(),
            'dispositivo' => [
                'id' => $dispositivo->id,
                'codigo' => $dispositivo->codigo,
                'estado_confirmado' => (int) $dispositivo->estado,
                'estado_solicitado' => $nuevoEstado,
            ]
        ], 202);
    }

    private function crearAlerta(string $tipo, string $nivel, ?string $sensorCodigo, ?int $dispositivoId, string $mensaje, array $contexto = [])
    {
        $sensorId = null;
        if ($sensorCodigo) {
            $sensorId = Sensor::where('codigo', $sensorCodigo)->value('id');
        }

        // Evita spamear: si ya existe una alerta igual abierta (no vista), no duplica
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
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
