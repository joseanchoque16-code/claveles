<x-app-layout header-title="Alertas">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Alertas</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nivel</th>
                                <th>Tipo</th>
                                <th>Mensaje</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alertas as $a)
                                <tr>
                                    <td>
                                        <span class="badge
                                            @if($a->nivel==='critical') bg-danger
                                            @elseif($a->nivel==='warning') bg-warning text-dark
                                            @else bg-info
                                            @endif
                                        ">
                                            {{ strtoupper($a->nivel) }}
                                        </span>
                                    </td>
                                    <td>{{ $a->tipo }}</td>
                                    <td>{{ $a->mensaje }}</td>
                                    <td class="text-muted">{{ $a->created_at }}</td>
                                    <td>
                                        @if(!$a->visto)
                                            <form method="POST" action="{{ route('alertas.visto', $a->id) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">Marcar visto</button>
                                            </form>
                                        @else
                                            <span class="badge bg-light text-muted">Visto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $alertas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
