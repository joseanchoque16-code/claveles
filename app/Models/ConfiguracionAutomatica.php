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

    /** Devuelve el modo global único que deben compartir la UI y los controladores. */
    public static function modoGlobal(): string
    {
        $modo = static::query()->orderBy('id')->value('modo_global');

        return in_array($modo, ['manual', 'automatico'], true) ? $modo : 'automatico';
    }
}
