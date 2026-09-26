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
      try { const j = await res.json(); if (j?.msg) msg = j.msg; } catch {}
      alert(msg);
      return;
    }

    // UI: activar el botón correcto
    wrap.querySelectorAll('.js-toggle').forEach(b => {
      const active = b.dataset.estado === desired;
      b.classList.toggle('is-active', active);
      b.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    // Actualizar badge "Estado"
    const card = wrap.closest('.card, .device-card, .actuator-card') || document;
    const badge = card.querySelector('.js-estado-badge');
    const isOn = desired === '1';
    if (badge) badge.textContent = isOn ? 'ON' : 'OFF';

    const stateWrap = card.querySelector('.js-estado-badge-wrap');
    if (stateWrap) {
      stateWrap.classList.remove('text-light-success', 'text-light-secondary');
      stateWrap.classList.add(isOn ? 'text-light-success' : 'text-light-secondary');

      const icon = stateWrap.querySelector('i');
      if (icon) icon.className = `ph-duotone ${isOn ? 'ph-check-circle' : 'ph-stop-circle'}`;
    }

  } catch (err) {
    console.error(err);
    alert('Error de red o servidor.');
  } finally {
    wrap.classList.remove('is-loading');
  }
});
