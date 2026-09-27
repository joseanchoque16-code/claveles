<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\ConfiguracionAutomatica;

class ActuadorController extends Controller
{
    public function index()
    {
        $dispositivos = Dispositivo::orderBy('id')->get();

        // modo global (manual/automatico)
        $modo = ConfiguracionAutomatica::modoGlobal();

        return view('dispositivos.index', compact('dispositivos', 'modo'));
    }
}
