<x-guest-layout>
    <div class="login-form-container">
        <div class="mb-4 text-center">
            <a class="logo d-inline-block" href="/">
                <img src="images/logo.webp" width="200" alt="Logo">
            </a>
        </div>

        <div class="form_container">
            <div class="app-form p-4 bg-white rounded shadow-sm">
                <div class="mb-4 text-center">
                    <h3 class="f-w-600">Verifica tu cuenta</h3>
                    <p class="text-secondary small">
                        {{ __('¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo haciendo clic en el enlace que te enviamos? Si no lo recibiste, te enviaremos otro con gusto.') }}
                    </p>
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success mb-4 small" role="alert">
                        {{ __('Se ha enviado un nuevo enlace de verificación a la dirección de correo que proporcionaste.') }}
                    </div>
                @endif

                <div class="d-flex flex-column gap-3 mt-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Reenviar correo de verificación') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="text-center">
                        @csrf
                        <button type="submit" class="btn btn-link text-decoration-none text-muted small">
                            <i class="fa fa-sign-out-alt me-1"></i> {{ __('Cerrar sesión') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>