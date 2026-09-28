<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\ConfiguracionAutomatica;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DispositivoController extends Controller
{
    private const GPIO_SALIDA_VALIDOS = [4, 13, 14, 16, 17, 18, 19, 21, 22, 25, 26, 32, 33];

    public function index()
    {
        $dispositivos = Dispositivo::orderBy('id')->get();
        $modo = ConfiguracionAutomatica::modoGlobal();

        return view('dispositivos.index', compact('dispositivos', 'modo'));
    }

    public function create()
    {
        $usedPins = Dispositivo::pluck('gpio_pin')
            ->merge(Sensor::where('activo', true)->whereNotNull('gpio_pin')->pluck('gpio_pin'))
            ->map(fn ($pin) => (int) $pin)
            ->all();

        return view('dispositivos.create', [
            'gpioPins' => array_values(array_filter(
                self::GPIO_SALIDA_VALIDOS,
                fn ($pin) => !in_array($pin, $usedPins, true)
            )),
        ]);
    }

    public function store(Request $request)
    {
        if (Dispositivo::count() >= 12) {
            return back()->withErrors([
                'codigo' => 'El firmware admite hasta 12 actuadores. Ya alcanzaste ese límite.',
            ])->withInput();
        }

        $request->merge([
            'codigo' => strtoupper(trim((string) $request->input('codigo', ''))),
        ]);

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'regex:/^D_[A-Z0-9_]+$/', Rule::unique('dispositivos', 'codigo')],
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::in(['rele'])],
            'gpio_pin' => [
                'required', 'integer', Rule::in(self::GPIO_SALIDA_VALIDOS),
                Rule::unique('dispositivos', 'gpio_pin'),
                function ($attribute, $value, $fail) {
                    if (Sensor::where('gpio_pin', $value)->where('activo', true)->exists()) {
                        $fail('Ese GPIO ya está asignado a un sensor activo.');
                    }
                },
            ],
            'invertido' => ['nullable', 'boolean'],
            'habilitado' => ['nullable', 'boolean'],
        ], [
            'codigo.regex' => 'Usa un código único con formato D_NOMBRE, por ejemplo D_BOMBA_AUX.',
            'gpio_pin.in' => 'Selecciona un GPIO de salida permitido. Los pines reservados para el tanque no están disponibles.',
            'gpio_pin.unique' => 'Ese GPIO ya está asignado a otro actuador.',
        ]);

        $data['invertido'] = $request->boolean('invertido');
        $data['habilitado'] = $request->boolean('habilitado');
        $data['estado'] = 0;

        DB::transaction(function () use ($data) {
            Dispositivo::create($data);
            DB::table('configuracion_automatica')->increment('config_version');
        });

        return redirect()
            ->route('actuadores.index')
            ->with('ok', 'Actuador agregado. El ESP32 lo recibirá en la próxima sincronización.');
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
