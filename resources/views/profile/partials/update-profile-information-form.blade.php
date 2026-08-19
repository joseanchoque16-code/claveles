<section class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 font-weight-bold mb-0">
            {{ __('Información del Perfil') }}
        </h2>
    </div>

    <div class="card-body">
        <p class="text-secondary small mb-4">
            {{ __("Actualice la información del perfil de su cuenta y la dirección de correo electrónico.") }}
        </p>

        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="mt-4">
            @csrf
            @method('patch')

            <div class="row">
                <div class="col-12 mb-3">
                    <label for="name" class="form-label">{{ __('Nombre') }}</label>
                    <input id="name" name="name" type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
                    <input id="email" name="email" type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email', $user->email) }}" required autocomplete="username">
                    
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 p-2 bg-light rounded border">
                            <p class="text-sm mb-1 text-muted">
                                {{ __('Su dirección de correo electrónico no está verificada.') }}
                            </p>
                            <button form="send-verification" class="btn btn-link p-0 text-sm text-decoration-none">
                                {{ __('Haga clic aquí para volver a enviar el correo de verificación.') }}
                            </button>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-success small font-weight-bold">
                                    {{ __('Se ha enviado un nuevo enlace de verificación a su dirección de correo electrónico.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="col-12 d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Guardar') }}
                    </button>

                    @if (session('status') === 'profile-updated')
                        <span class="text-success small animate__animated animate__fadeIn">
                            {{ __('Guardado.') }}
                        </span>
                    @endif
                </div>
            </div>
        </form>
    </div>
</section>