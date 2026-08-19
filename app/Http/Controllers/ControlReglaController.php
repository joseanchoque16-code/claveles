<?php

namespace App\Http\Controllers;

use App\Models\ControlRegla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlReglaController extends Controller
{
    public function index()
    {
        $reglas = ControlRegla::with(['sensor:id,nombre,codigo,unidad', 'dispositivo:id,nombre,codigo'])
            ->orderBy('prioridad')
            ->orderBy('id')
            ->get();

        return view('control-reglas.index', compact('reglas'));
    }

    public function edit(ControlRegla $control_regla)
    {
        $dias = [
            1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'
        ];

        // $control_regla es el mismo registro
        $regla = $control_regla->load(['sensor', 'dispositivo']);

        return view('control-reglas.edit', compact('regla', 'dias'));
    }

    public function update(Request $request, ControlRegla $control_regla)
    {
        $data = $request->validate([
            'activa'     => ['nullable', 'boolean'],
            'prioridad'  => ['required', 'integer', 'min:1', 'max:9999'],

            'umbral_on'  => ['nullable', 'numeric'],
            'umbral_off' => ['nullable', 'numeric'],

            'min_on_s'   => ['required', 'integer', 'min:0', 'max:86400'],
            'min_off_s'  => ['required', 'integer', 'min:0', 'max:86400'],

            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin'    => ['nullable', 'date_format:H:i'],

            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['integer', 'min:1', 'max:7'],
        ]);

        // Checkbox: si no viene, es false
        $data['activa'] = $request->boolean('activa');

        // Umbrales: o vienen ambos o ninguno
        $on  = $request->input('umbral_on');
        $off = $request->input('umbral_off');
        if (($on === null) xor ($off === null)) {
            return back()
                ->withErrors(['umbral_on' => 'Definí ON y OFF juntos, o dejalos ambos vacíos.'])
                ->withInput();
        }

        // Horas: o vienen ambas o ninguna
        if ($request->filled('hora_inicio') xor $request->filled('hora_fin')) {
            return back()
                ->withErrors(['hora_inicio' => 'Definí hora inicio y fin juntas, o dejalas vacías.'])
                ->withInput();
        }

        // Días: si no selecciona nada, guardamos null (interpreta “todos”)
        $data['dias_semana'] = $request->filled('dias_semana')
            ? array_values($request->dias_semana)
            : null;

        DB::transaction(function () use ($control_regla, $data) {
            $control_regla->update($data);

            // Bump config_version para que el ESP32 re-sincronice
            DB::table('configuracion_automatica')
                ->where('id', 1)
                ->update([
                    'config_version' => DB::raw('config_version + 1'),
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('control-reglas.index')
            ->with('ok', 'Regla actualizada. (config_version ++)');
    }
}
