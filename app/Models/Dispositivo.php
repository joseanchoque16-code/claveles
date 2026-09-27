<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $fillable = ['codigo', 'nombre', 'tipo', 'gpio_pin', 'invertido', 'estado', 'habilitado'];

    protected $casts = [
        'invertido' => 'boolean',
        'habilitado' => 'boolean',
        'estado' => 'integer',
    ];

    public function reglas() {
        return $this->hasMany(ControlRegla::class);
    }

    public function actuaciones() {
        return $this->hasMany(Actuacion::class);
    }

    public function comandos() {
        return $this->hasMany(IotCommand::class);
    }
}
