<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="200" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <form method="POST" action="{{ route('password.store') }}" class="app-form p-4 bg-white rounded shadow-sm">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4 text-center">
                    <h3 class="f-w-600">Nueva Contraseña</h3>
                    <p class="text-secondary small">Crea una clave segura para proteger tu invernadero IoT</p>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input id="email" type="email" name="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input id="password" type="password" name="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               required autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-4">
                        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                        <input id="password_confirmation" type="password" 
                               name="password_confirmation" class="form-control" 
                               required autocomplete="new-password">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Restablecer Contraseña') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>