<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    private function getOrCreateConfig()
    {
        $conf = DB::table('configuracion_automatica')->orderBy('id')->first();

        if (!$conf) {
            DB::table('configuracion_automatica')->insert([
                'modo_global' => 'automatico',
                'stale_min' => 10,
                'timezone' => 'America/La_Paz',
                'config_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $conf = DB::table('configuracion_automatica')->orderBy('id')->first();
        }

        return $conf;
    }

    // Operador + admin (solo lectura)
    public function index()
    {
        $conf = $this->getOrCreateConfig();
        return view('config.index', compact('conf'));
    }

    // Solo admin (edición)
    public function edit()
    {
        $conf = $this->getOrCreateConfig();
        return view('config.edit', compact('conf'));
    }

    // Solo admin (guardar)
    public function update(Request $request)
    {
        $data = $request->validate([
            'modo_global' => ['required', 'in:manual,automatico'],
            'stale_min'   => ['required', 'integer', 'min:1', 'max:1440'],
            'timezone'    => ['required', 'string', 'max:64'],
        ]);

        $conf = $this->getOrCreateConfig();

        DB::table('configuracion_automatica')
            ->where('id', $conf->id)
            ->update([
                'modo_global' => $data['modo_global'],
                'stale_min' => $data['stale_min'],
                'timezone' => $data['timezone'],
                'config_version' => DB::raw('config_version + 1'),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('config.edit')
            ->with('ok', 'Configuración actualizada. El ESP32 sincronizará en breve (config_version ++).');
    }
}
