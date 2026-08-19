<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lectura extends Model
{
    protected $fillable = ['sensor_id', 'valor', 'registrado_en'];

    protected $casts = [
        'valor' => 'float',
        'registrado_en' => 'datetime',
    ];

    public function sensor() {
        return $this->belongsTo(Sensor::class);
    }
    protected static function booted()
    {
        static::created(function (Lectura $lectura) {
            $lectura->sensor()->update(['valor_actual' => $lectura->valor]);
        });
    }
}
