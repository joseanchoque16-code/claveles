<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Actuacion;
use App\Models\Dispositivo;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        DB::transaction(function () use ($dispositivo, $nuevoEstado, $request) {

            $dispositivo->estado = $nuevoEstado;
            $dispositivo->save();

            Actuacion::create([
                'dispositivo_id' => $dispositivo->id,
                'control_regla_id' => null,
                'accion' => $nuevoEstado === 1 ? 'on' : 'off',
                'origen' => 'manual',
                'valor_sensor' => null,
                'motivo' => ['by_user_id' => $request->user()->id],
                'ejecutado_en' => now(),
            ]);

            // Para que el ESP32 se entere rápido (opcional, recomendado)
            DB::table('configuracion_automatica')->increment('config_version');
        });

        return response()->json([
            'ok' => true,
            'dispositivo' => [
                'id' => $dispositivo->id,
                'codigo' => $dispositivo->codigo,
                'estado' => $dispositivo->estado,
            ]
        ]);
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
