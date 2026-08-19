<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="200" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <form method="POST" action="{{ route('password.email') }}" class="app-form p-4 bg-white rounded shadow-sm">
                @csrf

                <div class="mb-4 text-center">
                    <h3 class="f-w-600">¿Olvidaste tu contraseña?</h3>
                    <p class="text-secondary small">
                        {{ __('No hay problema. Dinós tu correo y te enviaremos un enlace para elegir una nueva.') }}
                    </p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-4" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
                    <input id="email" type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           class="form-control @error('email') is-invalid @enderror" 
                           required autofocus>
                    
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100">
                        {{ __('Enviar enlace de restablecimiento') }}
                    </button>
                </div>

                <div class="mt-3 text-center">
                    <a href="{{ route('login') }}" class="text-decoration-none small text-muted">
                        <i class="fa fa-arrow-left me-1"></i> Volver al login
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>