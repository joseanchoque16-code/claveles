<x-app-layout header-title="Editar sensor">
    <div class="container-fluid">

        @if ($errors->any())
            <div class="alert alert-danger">
                <b>Revisá estos errores:</b>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('ok'))
            <div class="alert alert-success">
                {{ session('ok') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Editar: {{ $sensor->nombre }}</b>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('sensores.index') }}">Volver</a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('sensores.update', $sensor->id) }}">
                    @csrf
                    @method('PUT')

                    @php
                        $tipoActual = old('tipo', $sensor->tipo);
                    @endphp

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Código</label>
                            <input class="form-control" name="codigo"
                                   value="{{ old('codigo', $sensor->codigo) }}">
                        </div>

                        <div class="col-12 col-md-8">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre"
                                   value="{{ old('nombre', $sensor->nombre) }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo">
                                @foreach($tipos as $value => $label)
                                    <option value="{{ $value }}" {{ $tipoActual === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Elegí el tipo real del sensor.
                            </small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Unidad</label>
                            <input class="form-control" name="unidad"
                                   value="{{ old('unidad', $sensor->unidad) }}">
                            <small class="text-muted">Ej: °C, %, etc.</small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">GPIO pin</label>
                            <input type="number" class="form-control" name="gpio_pin" id="gpio_pin"
                                   value="{{ old('gpio_pin', $sensor->gpio_pin) }}">
                            <small class="text-muted" id="gpio_help">
                                DHT11: GPIO digitales recomendados. Soil/LDR: ADC 32, 33, 34, 35, 36, 39. Water level: null.
                            </small>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" value="1"
                                       {{ old('activo', $sensor->activo) ? 'checked' : '' }}>
                                <label class="form-check-label">Sensor activo</label>
                            </div>
                            <small class="text-muted">
                                Si lo desactivás, no se usa para lectura ni control automático.
                            </small>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary">Guardar</button>
                        <a class="btn btn-outline-secondary" href="{{ route('sensores.index') }}">Cancelar</a>
                    </div>

                    <small class="text-muted d-block mt-3">
                        Al guardar se incrementa <b>config_version</b> para que el ESP32 re-sincronice.
                    </small>
                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tipo = document.getElementById('tipo');
            const gpio = document.getElementById('gpio_pin');
            const help = document.getElementById('gpio_help');

            function updateGpioRules() {
                const val = tipo.value;

                if (val === 'water_level') {
                    gpio.value = '';
                    gpio.setAttribute('disabled', 'disabled');
                    help.textContent = 'Water level usa pines fijos internos del ESP32. Este campo se deja vacío.';
                    return;
                }

                gpio.removeAttribute('disabled');

                if (val === 'dht11_temp' || val === 'dht11_hum') {
                    help.textContent = 'DHT11: usar GPIO recomendados 4, 13, 14, 18, 19, 21, 22, 23, 25, 26, 27, 32 o 33.';
                } else if (val === 'soil_cap' || val === 'ldr') {
                    help.textContent = 'Soil/LDR: usar ADC válidos 32, 33, 34, 35, 36 o 39.';
                } else {
                    help.textContent = 'Ingresá un GPIO válido.';
                }
            }

            tipo.addEventListener('change', updateGpioRules);
            updateGpioRules();
        });
    </script>
</x-app-layout>