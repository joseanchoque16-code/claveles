<section class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 font-weight-bold text-danger mb-0">
            {{ __('Eliminar Cuenta') }}
        </h2>
    </div>

    <div class="card-body">
        <p class="text-secondary small mb-4">
            {{ __('Una vez que se elimine su cuenta, todos sus recursos y datos (incluidos los históricos de sensores IoT) se borrarán permanentemente.') }}
        </p>

        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
            {{ __('Eliminar Cuenta') }}
        </button>
    </div>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="deletionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-4">
                    @csrf
                    @method('delete')

                    <div class="modal-header border-0">
                        <h5 class="modal-title font-weight-bold" id="deletionModalLabel">
                            {{ __('¿Está seguro de que desea eliminar su cuenta?') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="text-secondary small">
                            {{ __('Por favor, ingrese su contraseña para confirmar que desea eliminar permanentemente su acceso al sistema.') }}
                        </p>

                        <div class="mt-3">
                            <label for="password_deletion" class="form-label sr-only">{{ __('Contraseña') }}</label>
                            <input id="password_deletion" 
                                   name="password" 
                                   type="password" 
                                   class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                                   placeholder="{{ __('Contraseña') }}">

                            @error('password', 'userDeletion')
                                <div class="invalid-feedback mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('Cancelar') }}
                        </button>

                        <button type="submit" class="btn btn-danger">
                            {{ __('Eliminar Cuenta') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>