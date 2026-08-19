<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionAutomatica extends Model
{
    protected $table = 'configuracion_automatica';

    protected $fillable = [
        'modo_global',
        'stale_min',
        'timezone',
        'config_version',
    ];
}
