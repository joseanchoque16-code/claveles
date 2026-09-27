<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotCommand extends Model
{
    protected $fillable = [
        'command_id', 'dispositivo_id', 'requested_by', 'accion', 'status',
        'resultado', 'requested_at', 'expires_at', 'acknowledged_at',
    ];

    protected $casts = [
        'resultado' => 'array',
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class);
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
