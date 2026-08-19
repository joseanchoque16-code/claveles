<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actuacion extends Model
{
    protected $table = 'actuaciones'; 
    protected $fillable = [
        'dispositivo_id', 'control_regla_id', 'accion', 
        'origen', 'valor_sensor', 'motivo', 'ejecutado_en'
    ];

    protected $casts = [
        'motivo' => 'array', // Para guardar detalles técnicos del porqué de la acción
        'valor_sensor' => 'float',
        'ejecutado_en' => 'datetime',
    ];

    public function dispositivo() { return $this->belongsTo(Dispositivo::class); }
    public function regla() { return $this->belongsTo(ControlRegla::class, 'control_regla_id'); }
}