<x-app-layout header-title="Agregar actuador">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Agregar actuador</h2>
                <small class="text-muted">Registra un relé nuevo para sincronizarlo con el ESP32.</small>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('actuadores.index') }}">Volver a actuadores</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><b>Datos del actuador</b></div>
            <div class="card-body">
                <form method="POST" action="{{ route('dispositivos.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input id="codigo" name="codigo" class="form-control" value="{{ old('codigo') }}"
                                   placeholder="D_BOMBA_AUX" maxlength="50" required>
                            <small class="text-muted">Debe ser único y comenzar con D_, por ejemplo D_VALVULA_2.</small>
                        </div>

                        <div class="col-md-5">
                            <label for="nombre" class="form-label">Nombre visible</label>
                            <input id="nombre" name="nombre" class="form-control" value="{{ old('nombre') }}"
                                   placeholder="Bomba auxiliar" maxlength="120" required>
                        </div>

                        <div class="col-md-3">
                            <label for="gpio_pin" class="form-label">GPIO de salida</label>
                            <select id="gpio_pin" name="gpio_pin" class="form-select" required>
                                <option value="">Selecciona un GPIO libre</option>
                                @foreach($gpioPins as $pin)
                                    <option value="{{ $pin }}" {{ (string) old('gpio_pin') === (string) $pin ? 'selected' : '' }}>
                                        GPIO {{ $pin }}
                                    </option>
                                @endforeach
                            </select>
                            @if(count($gpioPins) === 0)
                                <small class="text-danger">No quedan GPIO de salida disponibles en la configuración actual.</small>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select id="tipo" name="tipo" class="form-select" required>
                                <option value="rele" selected>Relé</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input id="invertido" name="invertido" type="checkbox" value="1" class="form-check-input"
                                       {{ old('invertido') ? 'checked' : '' }}>
                                <label for="invertido" class="form-check-label">Relé activo en LOW (invertir señal)</label>
                            </div>
                            <div class="form-check mt-2">
                                <input type="hidden" name="habilitado" value="0">
                                <input id="habilitado" name="habilitado" type="checkbox" value="1" class="form-check-input"
                                       {{ old('habilitado', 1) ? 'checked' : '' }}>
                                <label for="habilitado" class="form-check-label">Habilitado para sincronizarse con el ESP32</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        El actuador se creará apagado. Usa solo un GPIO físicamente libre; los GPIO reservados para el nivel de agua no aparecen aquí.
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" {{ count($gpioPins) === 0 ? 'disabled' : '' }}>
                            Guardar actuador
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('actuadores.index') }}">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
