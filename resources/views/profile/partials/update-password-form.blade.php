<section class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 font-weight-bold mb-0">
            {{ __('Actualizar Contraseña') }}
        </h2>
    </div>

    <div class="card-body">
        <p class="text-secondary small mb-4">
            {{ __('Asegúrese de que su cuenta esté usando una contraseña larga y aleatoria para mantenerse segura.') }}
        </p>

        <form method="post" action="{{ route('password.update') }}" class="mt-4">
            @csrf
            @method('put')

            <div class="row">
                <div class="col-12 mb-3">
                    <label for="update_password_current_password" class="form-label">{{ __('Contraseña Actual') }}</label>
                    <input id="update_password_current_password" name="current_password" type="password" 
                           class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                           autocomplete="current-password">
                    
                    @error('current_password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label for="update_password_password" class="form-label">{{ __('Nueva Contraseña') }}</label>
                    <input id="update_password_password" name="password" type="password" 
                           class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                           autocomplete="new-password">
                    
                    @error('password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mb-4">
                    <label for="update_password_password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }}</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" 
                           class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
                           autocomplete="new-password">
                    
                    @error('password_confirmation', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Guardar') }}
                    </button>

                    @if (session('status') === 'password-updated')
                        <span class="text-success small animate__animated animate__fadeOut animate__delay-2s">
                            {{ __('Guardado.') }}
                        </span>
                    @endif
                </div>
            </div>
        </form>
    </div>
</section>