<x-app-layout header-title="Reglas de control">
    <div class="container-fluid">

        @if(session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        <div class="card">
            <div class="card-header"><b>Control reglas</b></div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sensor</th>
                            <th>Dispositivo</th>
                            <th>Activa</th>
                            <th>Prioridad</th>
                            <th>ON</th>
                            <th>OFF</th>
                            <th>Min ON/OFF</th>
                            <th>Horario</th>
                            <th>Días</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($reglas as $r)
                            <tr>
                                <td>{{ $r->id }}</td>
                                <td>
                                    <b>{{ $r->sensor->nombre ?? '—' }}</b><br>
                                    <small class="text-muted">{{ $r->sensor->codigo ?? '' }}</small>
                                </td>
                                <td>
                                    <b>{{ $r->dispositivo->nombre ?? '—' }}</b><br>
                                    <small class="text-muted">{{ $r->dispositivo->codigo ?? '' }}</small>
                                </td>
                                <td>
                                    {!! $r->activa ? '<span class="badge bg-success">SI</span>' : '<span class="badge bg-secondary">NO</span>' !!}
                                </td>
                                <td>{{ $r->prioridad }}</td>
                                <td>{{ $r->umbral_on ?? '—' }}</td>
                                <td>{{ $r->umbral_off ?? '—' }}</td>
                                <td>{{ $r->min_on_s }} / {{ $r->min_off_s }}</td>
                                <td>
                                    {{ $r->hora_inicio ? substr($r->hora_inicio,0,5) : '—' }} -
                                    {{ $r->hora_fin ? substr($r->hora_fin,0,5) : '—' }}
                                </td>
                                <td>
                                    @php $ds = $r->dias_semana ?? null; @endphp
                                    <small class="text-muted">{{ is_array($ds) ? implode(',', $ds) : '—' }}</small>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('control-reglas.edit', $r->id) }}">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($reglas->isEmpty())
                        <div class="text-muted">No hay reglas cargadas.</div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
