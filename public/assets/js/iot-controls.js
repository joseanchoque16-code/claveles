// public/js/iot-controls.js

$(document).ready(function () {
    // Función para mostrar notificaciones (si usas un div #ui-msg)
    function showIotMsg(type, text) {
        const el = $('#ui-msg');
        if (el.length) {
            el.removeClass('d-none alert-success alert-danger alert-warning alert-info');
            el.addClass('alert-' + type);
            el.text(text);
            setTimeout(() => el.addClass('d-none'), 3500);
        }
    }

    // Handler universal para botones con clase .js-set-estado
    $(document).on('click', '.js-set-estado', function () {
        const btn = $(this);
        const id = btn.data('id');
        const estado = btn.data('estado');

        // Deshabilitar temporalmente para evitar doble clic
        const originalText = btn.text();
        btn.prop('disabled', true);

        $.ajax({
            url: `/dispositivos/${id}/manual`,
            method: 'POST',
            data: { estado },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function (response) {
            // Actualizar el Badge de estado
            const badge = $(`.js-badge-estado[data-id="${id}"]`);
            if (estado == 1) {
                badge.removeClass('bg-secondary').addClass('bg-success').text('ON');
                showIotMsg('success', 'Actuador encendido correctamente.');
            } else {
                badge.removeClass('bg-success').addClass('bg-secondary').text('OFF');
                showIotMsg('info', 'Actuador apagado correctamente.');
            }
        })
        .fail(function (xhr) {
            let msg = 'Error al cambiar el estado.';
            if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
            showIotMsg('warning', msg);
        })
        .always(function() {
            // Re-habilitar si el modo sigue siendo manual (opcional, refresco se encarga)
            btn.prop('disabled', false);
        });
    });
});