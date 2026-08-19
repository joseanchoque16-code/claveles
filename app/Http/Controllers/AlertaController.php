<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index()
    {
        $alertas = Alerta::orderByDesc('created_at')->paginate(30);
        return view('alertas.index', compact('alertas'));
    }

    public function marcarVisto(Alerta $alerta)
{
    $alerta->visto = true;
    $alerta->cerrado_en = now();
    $alerta->save();

    if (request()->expectsJson()) {
        return response()->json(['ok' => true]);
    }

    return back()->with('ok', 'Alerta marcada como vista.');
}

}
