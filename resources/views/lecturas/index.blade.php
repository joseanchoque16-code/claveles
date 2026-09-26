<x-app-layout header-title="Lecturas">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Lecturas</h2>
                <small class="text-muted">Filtrá por sensor y rango de tiempo</small>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('lecturas.index') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Sensor</label>
                        <select name="sensor_id" class="form-select">
                            <option value="">Todos (tabla)</option>
                            @foreach($sensores as $s)
                                <option value="{{ $s->id }}" {{ (int)($sensorId ?? 0) === (int)$s->id ? 'selected' : '' }}>
                                    {{ $s->nombre }} ({{ $s->codigo }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Para el gráfico necesitás elegir 1 sensor.</small>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="datetime-local" class="form-control" name="from"
                               value="{{ $from ? $from->format('Y-m-d\\TH:i') : '' }}">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="datetime-local" class="form-control" name="to"
                               value="{{ $to ? $to->format('Y-m-d\\TH:i') : '' }}">
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100">
                            <i class="ph-duotone ph-funnel"></i> Filtrar
                        </button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('lecturas.index') }}">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Resumen + gráfico (solo si hay sensor seleccionado) --}}
        @if($sensorId)
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">Sensor</div>
                            <div class="h5 mb-1">{{ $sensorSeleccionado->nombre ?? '—' }}</div>
                            <small class="text-muted">{{ $sensorSeleccionado->codigo ?? '' }}</small>

                            <hr>

                            <div class="text-muted">Última lectura</div>
                            <div class="h3 mb-0">
                                {{ $ultima?->valor ?? '—' }}
                                <small class="text-muted">{{ $sensorSeleccionado->unidad ?? '' }}</small>
                            </div>
                            <small class="text-muted">
                                {{ $ultima?->registrado_en ? $ultima->registrado_en : '—' }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header"><b>Gráfico</b></div>
                        <div class="card-body">
                            <canvas id="chartLecturas" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabla --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Lecturas registradas</b>
                <small class="text-muted">Mostrando {{ $lecturas->count() }} de {{ $lecturas->total() }}</small>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Sensor</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lecturas as $l)
                                <tr>
                                    <td>{{ $l->registrado_en }}</td>
                                    <td>
                                        <b>{{ $l->sensor->nombre ?? '—' }}</b><br>
                                        <small class="text-muted">{{ $l->sensor->codigo ?? '' }}</small>
                                    </td>
                                    <td>
                                        {{ $l->valor }}
                                        <small class="text-muted">{{ $l->sensor->unidad ?? '' }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No hay lecturas en el rango seleccionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $lecturas->links() }}
                </div>
            </div>
        </div>

    </div>

    @if($sensorId)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                (async function(){
                    const params = new URLSearchParams({
                        sensor_id: "{{ (int)$sensorId }}",
                        from: "{{ $from ? $from->format('Y-m-d H:i:s') : '' }}",
                        to: "{{ $to ? $to->format('Y-m-d H:i:s') : '' }}",
                        limit: 800
                    });

                    const res = await fetch("{{ route('lecturas.data', [], false) }}?" + params.toString());
                    const json = await res.json();

                    const ctx = document.getElementById('chartLecturas');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: json.labels,
                            datasets: [{
                                label: "{{ $sensorSeleccionado->nombre ?? 'Lecturas' }}",
                                data: json.values,
                                tension: 0.25,
                                pointRadius: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: true }
                            },
                            scales: {
                                x: { ticks: { maxTicksLimit: 8 } }
                            }
                        }
                    });
                })();
            </script>
        @endpush
    @endif
</x-app-layout>
