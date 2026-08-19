<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use Illuminate\Support\Facades\DB;

class ActuadorController extends Controller
{
    public function index()
    {
        $dispositivos = Dispositivo::orderBy('id')->get();

        // modo global (manual/automatico)
        $modo = DB::table('configuracion_automatica')->value('modo_global') ?? 'automatico';

        return view('dispositivos.index', compact('dispositivos', 'modo'));
    }
}
