<?php
// app/Models/Configuracion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'auto_riego',
        'auto_iluminacion',
        'auto_ventilador',
        'auto_calefaccion',

        'temp_set',
        'temp_hyst',

        'hum_amb_set',
        'hum_amb_hyst',

        'hum_suelo_set',
        'hum_suelo_hyst',

        'luz_set',
        'luz_hyst',

        'nivel_agua_min',

        'riego_duracion_s',
        'riego_bloqueo_min',

        'updated_by',
    ];
}
