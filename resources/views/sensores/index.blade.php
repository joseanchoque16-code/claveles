<x-app-layout header-title="Sensores">
    <div class="container-fluid">

        @if(session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        <div id="ui-msg" class="alert d-none" role="alert"></div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Sensores</h2>
                <small class="text-muted">Listado de sensores instalados</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Monitoreo</b>
                <small class="text-muted">{{ $sensores->count() }} sensores</small>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sensor</th>
                                <th>Tipo</th>
                                <th>GPIO</th>
                                <th>Estado</th>
                                <th>Valor actual</th>
                                <th>Última lectura</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($sensores as $s)
                            <tr>
                                <td>{{ $s->id }}</td>

                                <td>
                                    <b>{{ $s->nombre }}</b><br>
                                    <small class="text-muted">{{ $s->codigo }}</small>
                                </td>

                                <td><span class="badge bg-light text-dark">{{ $s->tipo }}</span></td>

                                <td>
                                    {{ is_null($s->gpio_pin) ? '—' : $s->gpio_pin }}
                                </td>

                                <td>
                                    {!! $s->activo
                                        ? '<span class="badge bg-success">ACTIVO</span>'
                                        : '<span class="badge bg-secondary">INACTIVO</span>' !!}
                                </td>

                                <td>
                                    {{ is_null($s->valor_actual) ? '—' : $s->valor_actual }}
                                    <small class="text-muted">{{ $s->unidad }}</small>
                                </td>

                                <td class="text-muted">
                                    {{ $s->last_reg ?? '—' }}
                                </td>

                                <td class="text-end d-flex justify-content-end gap-2">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('lecturas.index', ['sensor_id' => $s->id]) }}">
                                        Ver lecturas
                                    </a>

                                    @if(auth()->user()->role === 'admin')
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('sensores.edit', $s->id) }}">
                                            Editar
                                        </a>

                                        <button
                                            class="btn btn-sm {{ $s->activo ? 'btn-outline-danger' : 'btn-outline-success' }} js-toggle-sensor"
                                            data-id="{{ $s->id }}">
                                            {{ $s->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @if($sensores->isEmpty())
                            <tr><td colspan="8" class="text-muted">No hay sensores cargados.</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function showMsg(type, text) {
            const el = $('#ui-msg');
            el.removeClass('d-none alert-success alert-danger alert-warning alert-info');
            el.addClass('alert-' + type);
            el.text(text);
            setTimeout(() => el.addClass('d-none'), 3500);
        }

        $(document).on('click', '.js-toggle-sensor', function () {
            const id = $(this).data('id');
            const btn = $(this);

            $.ajax({
                url: `/sensores/${id}/toggle`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            })
            .done(function (r) {
                showMsg('success', 'Estado del sensor actualizado (config_version ++).');
                location.reload();
            })
            .fail(function () {
                showMsg('danger', 'No se pudo cambiar el estado del sensor.');
            });
        });
    </script>
    @endpush
</x-app-layout>
