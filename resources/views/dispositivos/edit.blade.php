<x-app-layout header-title="Editar dispositivo">
  <div class="container-fluid">

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div class="card border-0">
      <div class="card-header d-flex justify-content-between align-items-center">
        <b>{{ $dispositivo->nombre }}</b>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('dispositivos.index') }}">Volver</a>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('dispositivos.update', $dispositivo->id) }}">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Código</label>
              <input class="form-control" name="codigo" value="{{ old('codigo',$dispositivo->codigo) }}">
            </div>

            <div class="col-md-8">
              <label class="form-label">Nombre</label>
              <input class="form-control" name="nombre" value="{{ old('nombre',$dispositivo->nombre) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">GPIO pin</label>
              <input type="number" class="form-control" name="gpio_pin" value="{{ old('gpio_pin',$dispositivo->gpio_pin) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipo</label>
              <input class="form-control" name="tipo" value="{{ old('tipo',$dispositivo->tipo) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Estado (info)</label>
              <div class="mt-2">
                {!! $dispositivo->estado ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>' !!}
              </div>
            </div>

            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="invertido" value="1"
                  {{ old('invertido',$dispositivo->invertido) ? 'checked' : '' }}>
                <label class="form-check-label">Invertido (relé activo LOW)</label>
              </div>

              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="habilitado" value="1"
                  {{ old('habilitado',$dispositivo->habilitado) ? 'checked' : '' }}>
                <label class="form-check-label">Habilitado</label>
              </div>
            </div>
          </div>

          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn btn-outline-secondary" href="{{ route('dispositivos.index') }}">Cancelar</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</x-app-layout>
