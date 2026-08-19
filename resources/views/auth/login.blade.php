<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="250" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <form method="POST" action="{{ route('login') }}" class="app-form p-4 bg-white rounded shadow-sm">
                @csrf
                
                <div class="mb-3 text-center">
                    <h3 class="f-w-600">Bienvenido</h3>
                    <p class="text-secondary">Ingresa tus datos para continuar</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                    <label class="form-check-label" for="remember_me">Recordarme</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Ingresar</button>

                @if (Route::has('password.request'))
                    <div class="text-center">
                        <a href="{{ route('password.request') }}" class="text-decoration-none text-muted small">¿Olvidaste tu contraseña?</a>
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-guest-layout>
