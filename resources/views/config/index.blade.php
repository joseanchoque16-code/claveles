<x-app-layout header-title="Configuración">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Configuración (Solo lectura)</h2>
                <small class="text-muted">Disponible para operador y administrador</small>
            </div>

            @if(auth()->user()->role === 'admin')
                <a class="btn btn-outline-primary" href="{{ route('config.edit') }}">
                    <i class="ph-duotone ph-gear"></i> Editar configuración
                </a>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <div class="text-muted">Modo global</div>
                        <div class="h5 mb-0">
                            <span class="badge {{ ($conf->modo_global ?? '') === 'automatico' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ strtoupper($conf->modo_global ?? '—') }}
                            </span>
                        </div>
                        <small class="text-muted">En <b>manual</b>, el operador controla ON/OFF desde Actuadores.</small>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="text-muted">Stale (sensor caído)</div>
                        <div class="h5 mb-0">{{ $conf->stale_min ?? '—' }} <small class="text-muted">min</small></div>
                        <small class="text-muted">Minutos sin datos para generar alerta.</small>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="text-muted">Timezone</div>
                        <div class="h5 mb-0">{{ $conf->timezone ?? 'America/La_Paz' }}</div>
                        <small class="text-muted">Zona horaria usada para timestamps.</small>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="text-muted">
                            Versión de configuración (para sincronización ESP32):
                            <b>{{ $conf->config_version ?? 1 }}</b>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>
