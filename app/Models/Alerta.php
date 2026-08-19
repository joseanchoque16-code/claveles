<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = [
        'tipo', 'nivel', 'sensor_id', 'dispositivo_id', 
        'mensaje', 'contexto', 'visto', 'cerrado_en'
    ];

    protected $casts = [
        'contexto' => 'array',
        'visto' => 'boolean',
        'cerrado_en' => 'datetime',
    ];

    public function sensor() { return $this->belongsTo(Sensor::class); }
    public function dispositivo() { return $this->belongsTo(Dispositivo::class); }
}