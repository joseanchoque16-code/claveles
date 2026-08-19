<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    // Laravel busca la tabla 'sensors' por defecto, 
    // especificamos 'sensores' para que coincida con tu migración.
    protected $table = 'sensores';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'unidad', 
        'gpio_pin', 'activo', 'valor_actual'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'valor_actual' => 'decimal:3',
        'gpio_pin' => 'integer',
    ];

    /**
     * Relación: Un sensor tiene muchas lecturas históricas.
     */
    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'sensor_id');
    }

    /**
     * Relación: Un sensor puede estar vinculado a varias reglas de control.
     * Ejemplo: El sensor de temperatura controla el ventilador Y el calefactor.
     */
    public function reglas()
    {
        return $this->hasMany(ControlRegla::class, 'sensor_id');
    }

    /**
     * Relación: Un sensor puede disparar múltiples alertas.
     */
    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'sensor_id');
    }
}
