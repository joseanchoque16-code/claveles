document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.js-toggle');
  if (!btn) return;

  const wrap = btn.closest('.toggle-onoff');
  if (!wrap) return;

  const desired = btn.dataset.estado; // "1" o "0"

  // Si ya está activo, no hacer nada
  if (btn.classList.contains('is-active') || wrap.classList.contains('is-loading')) return;

  const url = wrap.dataset.url;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  wrap.classList.add('is-loading');

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf || '',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ estado: desired }),
    });

    // Manejo de 409 (regla tipo "nivel de agua bajo")
    if (res.status === 409) {
      let msg = 'No se pudo cambiar el estado (conflicto).';
      try { const j = await res.json(); if (j?.msg) msg = j.msg; } catch {}
      alert(msg);
      return;
    }

    if (!res.ok) {
      let msg = 'No se pudo cambiar el estado.';
      try { const j = await res.json(); if (j?.msg || j?.message) msg = j.msg || j.message; } catch {}
      alert(msg);
      return;
    }

    let result = {};
    try { result = await res.json(); } catch {}

    const card = wrap.closest('.card, .device-card, .actuator-card') || document;
    const badge = card.querySelector('.js-estado-badge');
    const isOn = desired === '1';
    const isPending = result.status === 'pending';

    // El modo de compatibilidad actualiza el estado deseado inmediatamente;
    // el protocolo ACK lo muestra pendiente hasta confirmar la salida física.
    if (!isPending) {
      wrap.querySelectorAll('.js-toggle').forEach(button => {
        const active = button.dataset.estado === desired;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
    }

    if (badge) badge.textContent = isPending
      ? `PENDIENTE: ${isOn ? 'ON' : 'OFF'}`
      : (isOn ? 'ON' : 'OFF');

    const stateWrap = card.querySelector('.js-estado-badge-wrap');
    if (stateWrap) {
      stateWrap.classList.remove('text-light-success', 'text-light-secondary');
      stateWrap.classList.add(isPending ? 'text-light-warning' : (isOn ? 'text-light-success' : 'text-light-secondary'));

      const icon = stateWrap.querySelector('i');
      if (icon) icon.className = `ph-duotone ${isPending ? 'ph-clock' : (isOn ? 'ph-check-circle' : 'ph-stop-circle')}`;
    }

  } catch (err) {
    console.error(err);
    alert('Error de red o servidor.');
  } finally {
    wrap.classList.remove('is-loading');
  }
});
