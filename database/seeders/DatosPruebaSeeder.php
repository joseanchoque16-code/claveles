<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =======================================================================
        // 1. USUARIO ADMINISTRADOR
        // =======================================================================
        DB::table('users')->insert([
            'name' => 'Admin IoT',
            'email' => 'admin@iot.com',
            'password' => Hash::make('password'), // Contraseña: password
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // =======================================================================
        // 2. CONFIGURACIÓN AUTOMÁTICA
        // =======================================================================
        // Esto evita el error de intentar insertar la PK 1 si ya existe
        DB::table('configuracion_automatica')->insertOrIgnore([
            'id' => 1,
            'modo_global' => 'automatico',
            'stale_min' => 5,
            'tz' => 'America/La_Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // =======================================================================
        // 3. SENSORES (IDs 1 y 2)
        // =======================================================================
        DB::table('sensores')->insert([
            [
                'id' => 1,
                'orden' => 1,
                'nombre' => 'Sensor de Temperatura',
                'tipo' => 'dht11',
                'gpio_pin' => 4,
                'gpio_pin2' => null,
                'valor_actual' => 25.5,
                'unidad' => '°C',
                'icono' => 'thermometer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'orden' => 2,
                'nombre' => 'Sensor de Humedad',
                'tipo' => 'dht11',
                'gpio_pin' => 4,
                'gpio_pin2' => null,
                'valor_actual' => 60.0,
                'unidad' => '%',
                'icono' => 'humidity',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // =======================================================================
        // 4. DISPOSITIVOS (Actuadores, IDs 1 y 2)
        // =======================================================================
        DB::table('dispositivos')->insert([
            [
                'id' => 1,
                'nombre' => 'Ventilador Principal',
                'tipo' => 'relay',
                'gpio_pin' => 12,
                'estado' => 0, // 0 = Apagado
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Luz de Cultivo',
                'tipo' => 'relay',
                'gpio_pin' => 13,
                'estado' => 1, // 1 = Encendido
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // =======================================================================
        // 5. REGLA DE AUTOMATIZACIÓN (ID 1)
        // =======================================================================
        // Regla: Si la temperatura (Sensor 1) es mayor a 28°C, ENCENDER Ventilador (Dispositivo 1)
        DB::table('reglas')->insert([
            'id' => 1,
            'sensor_id' => 1, // Sensor de Temperatura
            'tipo' => 'mañana',
            'valor_min' => 0,
            'valor_max' => 28.0, // Umbral para acción
            'hysteresis' => 1.00, // Valor de histeresis
            'hold_seconds' => 60,
            'accion' => 'encender', // Acción a tomar
            'dias' => 'lun,mar,mie,jue,vie,sab,dom',
            'orden' => 0,
            'habilitado' => 1,
            'dispositivo_id' => 1, // Ventilador Principal
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}