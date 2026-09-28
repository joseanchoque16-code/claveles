<x-app-layout header-title="Actuadores">
    @php
        $modoVista = in_array($modo ?? null, ['manual', 'automatico'], true)
            ? $modo
            : 'automatico';
    @endphp
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Actuadores</h2>
                <small class="text-muted">
                    Control manual ON/OFF · Modo:
                    <b class="{{ $modoVista === 'manual' ? 'text-warning' : 'text-success' }}">
                        {{ strtoupper($modoVista) }}
                    </b>
                </small>
            </div>

            @if(auth()->user()->role === 'admin')
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="{{ route('dispositivos.create') }}">
                        <i class="ph-duotone ph-plus"></i> Agregar actuador
                    </a>
                    <a class="btn btn-outline-primary" href="{{ route('config.edit') }}">
                        <i class="ph-duotone ph-gear"></i> Configuración
                    </a>
                </div>
            @endif
        </div>

        {{-- Mensajes --}}
        <div id="ui-msg" class="alert d-none" role="alert"></div>

        {{-- Aviso si está en automático --}}
        @if($modoVista !== 'manual')
            <div class="alert alert-info">
                El sistema está en <b>AUTOMÁTICO</b>. Para usar control manual, el admin debe cambiar a <b>MODO MANUAL</b>.
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Lista de actuadores</b>
                <small class="text-muted">{{ $dispositivos->count() }} dispositivos</small>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Actuador</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Habilitado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($dispositivos as $d)
                            @php
                                // Si tu columna no se llama "habilitado", cambiá acá por "activo" o la que uses.
                                $habil = (bool)($d->habilitado ?? true);

                                $isManualMode = ($modoVista === 'manual');
                                $disabled = (!$habil) || (!$isManualMode);
                            @endphp

                            <tr>
                                <td>{{ $d->id }}</td>

                                <td>
                                    <b>{{ $d->nombre }}</b>
                                    @if(isset($d->tipo))
                                        <br><small class="text-muted">{{ $d->tipo }}</small>
                                    @endif
                                </td>

                                <td><small class="text-muted">{{ $d->codigo }}</small></td>

                                <td>
                                    <span class="badge js-badge-estado {{ $d->estado ? 'bg-success' : 'bg-secondary' }}"
                                          data-id="{{ $d->id }}">
                                        {{ $d->estado ? 'ON' : 'OFF' }}
                                    </span>
                                </td>

                                <td>
                                    {!! $habil
                                        ? '<span class="badge bg-success">SI</span>'
                                        : '<span class="badge bg-secondary">NO</span>' !!}
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button
                                            class="btn btn-sm btn-success js-set-estado"
                                            data-id="{{ $d->id }}"
                                            data-estado="1"
                                            {{ $disabled ? 'disabled' : '' }}>
                                            ON
                                        </button>

                                        <button
                                            class="btn btn-sm btn-outline-secondary js-set-estado"
                                            data-id="{{ $d->id }}"
                                            data-estado="0"
                                            {{ $disabled ? 'disabled' : '' }}>
                                            OFF
                                        </button>

                                        @if(auth()->user()->role === 'admin')
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="{{ route('dispositivos.edit', $d->id) }}">
                                                Configurar
                                            </a>
                                        @endif
                                    </div>

                                    @if(!$isManualMode)
                                        <div class="small text-muted mt-1">Bloqueado por modo automático</div>
                                    @elseif(!$habil)
                                        <div class="small text-danger mt-1">Deshabilitado</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @if($dispositivos->isEmpty())
                            <tr><td colspan="6" class="text-muted">No hay actuadores cargados.</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                <small class="text-muted d-block mt-2">
                    * En manual, los relés siguen igual: <b>0=OFF</b> y <b>1=ON</b>.
                </small>
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

        $(document).on('click', '.js-set-estado', function () {
            const id = $(this).data('id');
            const estado = $(this).data('estado');

            $.ajax({
                url: `/dispositivos/${id}/manual`,
                method: 'POST',
                data: { estado },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            })
            .done(function () {
                const badge = $(`.js-badge-estado[data-id="${id}"]`);
                if (estado == 1) {
                    badge.removeClass('bg-secondary').addClass('bg-success').text('ON');
                    showMsg('success', 'Actuador encendido.');
                } else {
                    badge.removeClass('bg-success').addClass('bg-secondary').text('OFF');
                    showMsg('info', 'Actuador apagado.');
                }
            })
            .fail(function (xhr) {
                let msg = 'No se pudo cambiar el estado.';
                if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
                showMsg('warning', msg);
            });
        });
    </script>
    @endpush
</x-app-layout>
