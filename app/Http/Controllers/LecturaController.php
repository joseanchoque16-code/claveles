<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    public function index(Request $request)
    {
        $sensores = Sensor::where('activo', true)
            ->orderBy('nombre')
            ->get(['id','nombre','codigo','unidad']);

        $sensorId = $request->integer('sensor_id');

        // Default: últimas 24h
        $to   = $request->input('to')   ? Carbon::parse($request->input('to'))   : now();
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subDay();

        $query = Lectura::with('sensor')
            ->when($sensorId, fn($q) => $q->where('sensor_id', $sensorId))
            ->whereBetween('registrado_en', [$from, $to])
            ->orderByDesc('registrado_en');

        $lecturas = $query->paginate(50)->withQueryString();

        $sensorSeleccionado = $sensorId ? $sensores->firstWhere('id', $sensorId) : null;

        // Última lectura del sensor seleccionado (para card resumen)
        $ultima = null;
        if ($sensorId) {
            $ultima = Lectura::where('sensor_id', $sensorId)
                ->orderByDesc('registrado_en')
                ->first();
        }

        return view('lecturas.index', compact(
            'sensores', 'lecturas',
            'sensorId', 'from', 'to',
            'sensorSeleccionado', 'ultima'
        ));
    }

    // JSON para Chart.js
    public function data(Request $request)
    {
        $data = $request->validate([
            'sensor_id' => ['required','integer','exists:sensores,id'],
            'from' => ['nullable'],
            'to'   => ['nullable'],
            'limit'=> ['nullable','integer','min:50','max:5000'],
        ]);

        $sensorId = (int)$data['sensor_id'];
        $limit = $data['limit'] ?? 800;

        $to   = isset($data['to'])   ? Carbon::parse($data['to'])   : now();
        $from = isset($data['from']) ? Carbon::parse($data['from']) : now()->subDay();

        $rows = Lectura::where('sensor_id', $sensorId)
            ->whereBetween('registrado_en', [$from, $to])
            ->orderBy('registrado_en')
            ->limit($limit)
            ->get(['registrado_en','valor']);

        return response()->json([
            'labels' => $rows->map(fn($r) => $r->registrado_en->format('Y-m-d H:i'))->values(),
            'values' => $rows->map(fn($r) => (float)$r->valor)->values(),
        ]);
    }
}
