<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Lectura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SensorController extends Controller
{
    public function index()
    {
        $sub = Lectura::select('sensor_id', DB::raw('MAX(registrado_en) as last_reg'))
            ->groupBy('sensor_id');

        $sensores = Sensor::leftJoinSub($sub, 'l', function ($join) {
                $join->on('sensores.id', '=', 'l.sensor_id');
            })
            ->select('sensores.*', 'l.last_reg')
            ->orderBy('sensores.id')
            ->get();

        return view('sensores.index', compact('sensores'));
    }

    public function edit(Sensor $sensor)
    {
        $tipos = [
            'dht11_temp'  => 'DHT11 Temperatura',
            'dht11_hum'   => 'DHT11 Humedad',
            'soil_cap'    => 'Humedad de suelo capacitiva',
            'ldr'         => 'Luz (LDR)',
            'water_level' => 'Nivel de agua',
        ];

        return view('sensores.edit', compact('sensor', 'tipos'));
    }

    public function update(Request $request, Sensor $sensor)
    {
        $data = $request->validate([
            'codigo'   => ['required', 'string', 'max:50', Rule::unique('sensores', 'codigo')->ignore($sensor->id)],
            'nombre'   => ['required', 'string', 'max:120'],
            'tipo'     => ['required', 'string', Rule::in(['dht11_temp', 'dht11_hum', 'soil_cap', 'ldr', 'water_level'])],
            'unidad'   => ['nullable', 'string', 'max:20'],
            'gpio_pin' => ['nullable', 'integer', 'min:0', 'max:39'],
            'activo'   => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo');

        // Normalización por tipo
        $tipo = $data['tipo'];
        $gpio = $data['gpio_pin'] ?? null;

        // Para water_level no se usa gpio_pin en BD, porque los pines siguen fijos en el ESP32
        if ($tipo === 'water_level') {
            $data['gpio_pin'] = null;
        }

        // Validaciones por tipo
        if (in_array($tipo, ['dht11_temp', 'dht11_hum'], true)) {
            $gpioPermitidosDht = [4, 13, 14, 18, 19, 21, 22, 23, 25, 26, 27, 32, 33];

            if ($gpio === null || !in_array((int) $gpio, $gpioPermitidosDht, true)) {
                return back()
                    ->withErrors([
                        'gpio_pin' => 'Para DHT11 usa un GPIO recomendado: 4, 13, 14, 18, 19, 21, 22, 23, 25, 26, 27, 32 o 33.'
                    ])
                    ->withInput();
            }
        }

        if (in_array($tipo, ['soil_cap', 'ldr'], true)) {
            $gpioPermitidosAdc = [32, 33, 34, 35, 36, 39];

            if ($gpio === null || !in_array((int) $gpio, $gpioPermitidosAdc, true)) {
                return back()
                    ->withErrors([
                        'gpio_pin' => 'Para soil_cap o ldr usa un pin ADC válido: 32, 33, 34, 35, 36 o 39.'
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($sensor, $data) {
            $sensor->update($data);

            DB::table('configuracion_automatica')
                ->where('id', 1)
                ->update([
                    'config_version' => DB::raw('config_version + 1'),
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('sensores.index')
            ->with('ok', 'Sensor actualizado correctamente. config_version incrementado.');
    }

    public function toggle(Sensor $sensor)
    {
        DB::transaction(function () use ($sensor) {
            $sensor->activo = !$sensor->activo;
            $sensor->save();

            DB::table('configuracion_automatica')
                ->where('id', 1)
                ->update([
                    'config_version' => DB::raw('config_version + 1'),
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'ok' => true,
            'activo' => (bool) $sensor->activo,
        ]);
    }
}