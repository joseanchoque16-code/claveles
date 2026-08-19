<x-app-layout header-title="Dashboard">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Dashboard</h2>
                <small class="text-muted">
                    Rol: <b>{{ auth()->user()->role }}</b>
                </small>
            </div>

            @if(in_array(auth()->user()->role, ['admin','operador']))
                <a class="btn btn-outline-primary" href="{{ route('alertas.index') }}">
                    <i class="ph-duotone ph-bell"></i> Ver alertas
                </a>
            @endif
        </div>

        {{-- SENSORES --}}
        <div class="row g-3 mb-4">
            @foreach($sensores as $s)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted">{{ $s->nombre }}</div>
                                    <div class="h3 mb-0">
                                        {{ $s->valor_actual ?? '—' }}
                                        <small class="text-muted">{{ $s->unidad }}</small>
                                    </div>
                                    <small class="text-muted">{{ $s->codigo }}</small>
                                </div>
                                <div class="text-muted">
                                    <i class="ph-duotone ph-activity"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ACTUADORES --}}
        @if(in_array(auth()->user()->role, ['admin','operador']))
            <div class="row g-3 mb-4">
                @foreach($dispositivos as $d)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-muted">{{ $d->nombre }}</div>
                                        <div class="h5 mb-0">
                                            Estado: {!! $d->estado ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>' !!}
                                        </div>
                                        <small class="text-muted">{{ $d->codigo }}</small>
                                    </div>
                                    <i class="ph-duotone ph-plug"></i>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <button
                                        class="btn btn-sm btn-success w-50 js-set-estado"
                                        data-id="{{ $d->id }}"
                                        data-estado="1"
                                        {{ !$d->habilitado ? 'disabled' : '' }}
                                    >
                                        ON
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-secondary w-50 js-set-estado"
                                        data-id="{{ $d->id }}"
                                        data-estado="0"
                                        {{ !$d->habilitado ? 'disabled' : '' }}
                                    >
                                        OFF
                                    </button>
                                </div>

                                @if(!$d->habilitado)
                                    <small class="text-danger d-block mt-2">Deshabilitado</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ALERTAS RECIENTES --}}
        @if(in_array(auth()->user()->role, ['admin','operador']))
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <b>Alertas recientes</b>
                    <a href="{{ route('alertas.index') }}" class="btn btn-sm btn-outline-primary">Ver todo</a>
                </div>
                <div class="card-body">
                    @forelse($alertas as $a)
                        <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                            <div>
                                <span class="badge
                                    @if($a->nivel==='critical') bg-danger
                                    @elseif($a->nivel==='warning') bg-warning text-dark
                                    @else bg-info
                                    @endif
                                ">
                                    {{ strtoupper($a->nivel) }}
                                </span>

                                <span class="ms-2">{{ $a->mensaje }}</span>
                                <div class="text-muted small">{{ $a->created_at }}</div>
                            </div>

                            @if(!$a->visto)
                                <form method="POST" action="{{ route('alertas.visto', $a->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Marcar visto</button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted">Visto</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">Sin alertas 👍</div>
                    @endforelse
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
    <script>
        $(document).on('click', '.js-set-estado', function () {
            const id = $(this).data('id');
            const estado = $(this).data('estado');

            $.ajax({
                url: `/dispositivos/${id}/manual`,
                method: 'POST',
                data: { estado },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .done(function (r) {
                location.reload();
            })
            .fail(function (xhr) {
                let msg = 'No se pudo cambiar el estado';
                if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
                alert(msg);
            });
        });
    </script>
    @endpush
</x-app-layout>
