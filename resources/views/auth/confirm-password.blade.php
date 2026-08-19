<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="200" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <form method="POST" action="{{ route('password.confirm') }}" class="app-form p-4 bg-white rounded shadow-sm">
                @csrf

                <div class="mb-4 text-center">
                    <h3 class="f-w-600">Área Segura</h3>
                    <p class="text-secondary small">
                        {{ __('Esta es un área segura. Por favor, confirma tu contraseña antes de continuar.') }}
                    </p>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Contraseña') }}</label>
                    <input id="password" type="password" 
                           name="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           required autocomplete="current-password">
                    
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary w-100">
                        {{ __('Confirmar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>