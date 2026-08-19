<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlRegla extends Model
{
    protected $table = 'control_reglas';

    protected $fillable = [
        'sensor_id', 'dispositivo_id', 'activa', 'prioridad', 
        'umbral_on', 'umbral_off', 'min_on_s', 'min_off_s', 
        'hora_inicio', 'hora_fin', 'dias_semana'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'dias_semana' => 'array', // Maneja el JSON automáticamente
        'umbral_on'  => 'decimal:3',
        'umbral_off' => 'decimal:3',

    ];

    public function sensor() { return $this->belongsTo(Sensor::class); }
    public function dispositivo() { return $this->belongsTo(Dispositivo::class); }
}