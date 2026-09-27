<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InvernaderoClavelesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Config global (1 fila)
            DB::table('configuracion_automatica')->insert([
                'modo_global'   => 'automatico',
                'stale_min'     => 10,
                'timezone'      => 'America/La_Paz',
                'config_version'=> 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 2) Sensores (GPIO fijos ESP32 WROOM 38 pines)
            // DHT11 DATA en GPIO23 (temp + HR comparten pin)
            $sensores = [
                [
                    'codigo' => 'S_TEMP',
                    'nombre' => 'Temperatura ambiente',
                    'tipo'   => 'dht11_temp',
                    'unidad' => '°C',
                    'gpio_pin' => 23,
                    'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'S_HR',
                    'nombre' => 'Humedad relativa',
                    'tipo'   => 'dht11_hum',
                    'unidad' => '%',
                    'gpio_pin' => 23,
                    'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'S_HSUELO',
                    'nombre' => 'Humedad de suelo',
                    'tipo'   => 'soil_cap',
                    'unidad' => '%',
                    'gpio_pin' => 34, // ADC1
                    'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'S_LUZ',
                    'nombre' => 'Luz (LDR)',
                    'tipo'   => 'ldr',
                    'unidad' => '%',
                    'gpio_pin' => 35, // ADC1
                    'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'S_NIVEL',
                    'nombre' => 'Nivel de agua',
                    'tipo'   => 'water_level',
                    'unidad' => '%',
                    'gpio_pin' => null, // derivado de entradas 25/50/75/100
                    'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ];
            DB::table('sensores')->insert($sensores);

            $sensorIds = DB::table('sensores')->pluck('id', 'codigo');

            // 3) Dispositivos (relés)
            // Si tus relés son "activo LOW", poné invertido=1
            $dispositivos = [
                [
                    'codigo' => 'D_RIEGO',
                    'nombre' => 'Riego (bomba/válvula)',
                    'tipo' => 'rele',
                    'gpio_pin' => 25,
                    'invertido' => 0,
                    'estado' => 0,
                    'habilitado' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'D_LUZ',
                    'nombre' => 'Iluminación',
                    'tipo' => 'rele',
                    'gpio_pin' => 26,
                    'invertido' => 0,
                    'estado' => 0,
                    'habilitado' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'D_FAN',
                    'nombre' => 'Ventilador/Extractor',
                    'tipo' => 'rele',
                    'gpio_pin' => 27,
                    'invertido' => 0,
                    'estado' => 0,
                    'habilitado' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'codigo' => 'D_CALEF',
                    'nombre' => 'Calefacción (opcional)',
                    'tipo' => 'rele',
                    'gpio_pin' => 14,
                    'invertido' => 0,
                    'estado' => 0,
                    'habilitado' => 0, // opcional: deshabilitado por defecto
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ];
            DB::table('dispositivos')->insert($dispositivos);

            $dispIds = DB::table('dispositivos')->pluck('id', 'codigo');

            // 4) Reglas automáticas (histéresis + anti-ciclado)
            // Nota: para FAN usamos dos reglas (Temp + HR). Combinación recomendada:
            // - Si cualquiera pide ON => ON
            // - Si ambas piden OFF (ninguna pide ON) => OFF
            DB::table('control_reglas')->insert([
                // Riego por humedad de suelo
                [
                    'sensor_id' => $sensorIds['S_HSUELO'],
                    'dispositivo_id' => $dispIds['D_RIEGO'],
                    'activa' => 1,
                    'prioridad' => 10,
                    'umbral_on' => 60.000,   // ON si <= 60
                    'umbral_off' => 75.000,  // OFF si >= 75
                    'min_on_s' => 30,
                    'min_off_s' => 120,
                    'hora_inicio' => null,
                    'hora_fin' => null,
                    'dias_semana' => json_encode([1,2,3,4,5,6,7]),
                    'created_at' => now(), 'updated_at' => now(),
                ],

                // FAN por temperatura
                [
                    'sensor_id' => $sensorIds['S_TEMP'],
                    'dispositivo_id' => $dispIds['D_FAN'],
                    'activa' => 1,
                    'prioridad' => 10,
                    'umbral_on' => 22.000,   // Tesis: ventilación ON si T >= 22 °C
                    'umbral_off' => 18.000,  // Tesis: ventilación OFF si T <= 18 °C
                    'min_on_s' => 60,
                    'min_off_s' => 60,
                    'hora_inicio' => null,
                    'hora_fin' => null,
                    'dias_semana' => json_encode([1,2,3,4,5,6,7]),
                    'created_at' => now(), 'updated_at' => now(),
                ],

                // FAN por humedad relativa
                [
                    'sensor_id' => $sensorIds['S_HR'],
                    'dispositivo_id' => $dispIds['D_FAN'],
                    'activa' => 1,
                    'prioridad' => 10,
                    'umbral_on' => 75.000,   // Tesis: ventilación ON si HR >= 75 %
                    'umbral_off' => 65.000,  // Tesis: ventilación OFF si HR <= 65 %
                    'min_on_s' => 60,
                    'min_off_s' => 60,
                    'hora_inicio' => null,
                    'hora_fin' => null,
                    'dias_semana' => json_encode([1,2,3,4,5,6,7]),
                    'created_at' => now(), 'updated_at' => now(),
                ],

                // Luz por LDR + horario (06:00-18:00)
                [
                    'sensor_id' => $sensorIds['S_LUZ'],
                    'dispositivo_id' => $dispIds['D_LUZ'],
                    'activa' => 1,
                    'prioridad' => 20,
                    'umbral_on' => 40.000,   // ON si "luz" baja
                    'umbral_off' => 55.000,  // OFF si "luz" sube
                    'min_on_s' => 30,
                    'min_off_s' => 30,
                    'hora_inicio' => '06:00:00',
                    'hora_fin' => '18:00:00',
                    'dias_semana' => json_encode([1,2,3,4,5,6,7]),
                    'created_at' => now(), 'updated_at' => now(),
                ],

                // Calefacción (opcional)
                [
                    'sensor_id' => $sensorIds['S_TEMP'],
                    'dispositivo_id' => $dispIds['D_CALEF'],
                    'activa' => 0,          // apagada por defecto
                    'prioridad' => 30,
                    'umbral_on' => 10.000,  // Tesis: calefacción ON si T <= 10 °C
                    'umbral_off' => 14.000, // Tesis: calefacción OFF si T >= 14 °C
                    'min_on_s' => 120,
                    'min_off_s' => 120,
                    'hora_inicio' => null,
                    'hora_fin' => null,
                    'dias_semana' => json_encode([1,2,3,4,5,6,7]),
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ]);

        });
    }
}
