@props([
  'isOn' => false,
  'url' => '',
  'id' => null,
  'disabled' => false,
])

@php
  $isOn = (bool) $isOn;
  $disabled = (bool) $disabled;
@endphp

<div class="toggle-onoff {{ $disabled ? 'is-disabled' : '' }}"
     data-id="{{ $id }}"
     data-url="{{ $url }}"
     data-disabled="{{ $disabled ? 1 : 0 }}">
  <button type="button"
          class="toggle-onoff__btn js-toggle {{ $isOn ? 'is-active' : '' }}"
          data-estado="1"
          aria-pressed="{{ $isOn ? 'true' : 'false' }}"
          {{ $disabled ? 'disabled' : '' }}>
    ON
  </button>

  <button type="button"
          class="toggle-onoff__btn js-toggle {{ !$isOn ? 'is-active' : '' }}"
          data-estado="0"
          aria-pressed="{{ !$isOn ? 'true' : 'false' }}"
          {{ $disabled ? 'disabled' : '' }}>
    OFF
  </button>
</div>
