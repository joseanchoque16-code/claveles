<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\ConfiguracionAutomatica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispositivoController extends Controller
{
    public function index()
    {
        $dispositivos = Dispositivo::orderBy('id')->get();
        $modo = ConfiguracionAutomatica::modoGlobal();

        return view('dispositivos.index', compact('dispositivos', 'modo'));
    }

    public function edit(Dispositivo $dispositivo)
    {
        return view('dispositivos.edit', compact('dispositivo'));
    }

    public function update(Request $request, Dispositivo $dispositivo)
    {
        $data = $request->validate([
            'codigo'     => ['required','string','max:50','unique:dispositivos,codigo,'.$dispositivo->id],
            'nombre'     => ['required','string','max:120'],
            'tipo'       => ['nullable','string','max:30'],
            'gpio_pin'   => ['required','integer','min:0','max:39'],
            'invertido'  => ['nullable','boolean'],
            'habilitado' => ['nullable','boolean'],
        ]);

        $data['invertido']  = $request->boolean('invertido');
        $data['habilitado'] = $request->boolean('habilitado');

        DB::transaction(function () use ($dispositivo, $data) {
            $dispositivo->update($data);

            // Para que el ESP32 re-sincronice configuración
            DB::table('configuracion_automatica')->increment('config_version');
        });

        return redirect()->route('dispositivos.index')->with('ok', 'Dispositivo actualizado.');
    }
}
