<x-app-layout header-title="Editar regla">
    <div class="container-fluid">

        @if(session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <b>Revisá esto:</b>
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Regla #{{ $regla->id }}</b>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('control-reglas.index') }}">Volver</a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('control-reglas.update', $regla->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sensor</label>
                            <input class="form-control" value="{{ $regla->sensor->nombre ?? '—' }}" disabled>
                            <small class="text-muted">{{ $regla->sensor->codigo ?? '' }}</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dispositivo</label>
                            <input class="form-control" value="{{ $regla->dispositivo->nombre ?? '—' }}" disabled>
                            <small class="text-muted">{{ $regla->dispositivo->codigo ?? '' }}</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Activa</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="activa" value="1"
                                       {{ old('activa', $regla->activa) ? 'checked' : '' }}>
                                <label class="form-check-label">Habilitada</label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Prioridad</label>
                            <input type="number" class="form-control" name="prioridad"
                                   value="{{ old('prioridad', $regla->prioridad) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Umbral ON</label>
                            <input type="number" step="0.001" class="form-control" name="umbral_on"
                                   value="{{ old('umbral_on', $regla->umbral_on) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Umbral OFF</label>
                            <input type="number" step="0.001" class="form-control" name="umbral_off"
                                   value="{{ old('umbral_off', $regla->umbral_off) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Min ON (s)</label>
                            <input type="number" class="form-control" name="min_on_s"
                                   value="{{ old('min_on_s', $regla->min_on_s) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Min OFF (s)</label>
                            <input type="number" class="form-control" name="min_off_s"
                                   value="{{ old('min_off_s', $regla->min_off_s) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control" name="hora_inicio"
                                   value="{{ old('hora_inicio', $regla->hora_inicio ? substr($regla->hora_inicio,0,5) : '') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Hora fin</label>
                            <input type="time" class="form-control" name="hora_fin"
                                   value="{{ old('hora_fin', $regla->hora_fin ? substr($regla->hora_fin,0,5) : '') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Días</label>
                            @php
                                $sel = old('dias_semana', $regla->dias_semana ?? []);
                                $sel = is_array($sel) ? $sel : [];
                            @endphp

                            <div class="d-flex flex-wrap gap-3">
                                @foreach($dias as $num => $label)
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_semana[]"
                                               value="{{ $num }}" {{ in_array($num, $sel) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <small class="text-muted">
                                Si dejás vacío: se interpreta como “todos los días”.
                            </small>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary">Guardar</button>
                        <a class="btn btn-outline-secondary" href="{{ route('control-reglas.index') }}">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
