<x-app-layout header-title="Configuración">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Configuración</h2>
                <small class="text-muted">Solo administrador · impacta en el modo automático del ESP32</small>
            </div>
        </div>

        @if(session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <b>Corrige lo siguiente:</b>
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">

                <form method="POST" action="{{ route('config.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <label class="form-label">Modo global</label>
                            <select name="modo_global" class="form-select">
                                <option value="automatico" {{ ($conf->modo_global ?? '') === 'automatico' ? 'selected' : '' }}>
                                    Automático (ESP32 controla)
                                </option>
                                <option value="manual" {{ ($conf->modo_global ?? '') === 'manual' ? 'selected' : '' }}>
                                    Manual (Operador controla ON/OFF)
                                </option>
                            </select>
                            <small class="text-muted">
                                En <b>manual</b>, el ESP32 aplicará los estados enviados desde el panel.
                            </small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Stale (sensor caído)</label>
                            <input type="number" name="stale_min" class="form-control"
                                   value="{{ old('stale_min', $conf->stale_min ?? 10) }}" min="1" max="1440">
                            <small class="text-muted">Minutos sin datos para generar alerta.</small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Timezone</label>
                            <input type="text" name="timezone" class="form-control"
                                   value="{{ old('timezone', $conf->timezone ?? 'America/La_Paz') }}">
                            <small class="text-muted">Ej: America/La_Paz</small>
                        </div>

                        <div class="col-12">
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    Versión de configuración:
                                    <b>{{ $conf->config_version ?? 1 }}</b>
                                    <br>
                                    <small>Se incrementa automáticamente al guardar (para sincronización ESP32).</small>
                                </div>
                                <button class="btn btn-primary">
                                    <i class="ph-duotone ph-floppy-disk"></i> Guardar cambios
                                </button>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>

    </div>
</x-app-layout>
