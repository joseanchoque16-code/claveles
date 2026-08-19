<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="200" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <form method="POST" action="{{ route('register') }}" class="app-form p-4 bg-white rounded shadow-sm">
                @csrf

                <div class="mb-3 text-center">
                    <h3 class="f-w-600">Crear Cuenta</h3>
                    <p class="text-secondary small">Únete a la gestión inteligente de tu invernadero</p>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="name" class="form-label">Nombre Completo</label>
                        <input id="name" type="text" name="name" 
                               class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" required autofocus autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input id="email" type="email" name="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               value="{{ old('email') }}" required autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" type="password" name="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               required autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-4">
                        <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" 
                               name="password_confirmation" class="form-control" 
                               required autocomplete="new-password">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            Registrarse
                        </button>
                    </div>

                    <div class="col-12 text-center">
                        <a class="text-sm text-secondary text-decoration-none" href="{{ route('login') }}">
                            ¿Ya estás registrado? <span class="text-primary">Inicia sesión</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>