@props(['call', 'label', 'savedLabel' => 'Uloženo', 'errorLabel' => 'Chyba, zkuste znovu', 'loadingLabel' => 'Ukládám…'])
<button
    type="button"
    x-data="{ state: 'idle' }"
    x-on:click="
        if (state === 'loading') return;
        state = 'loading';
        $wire.{{ $call }}
            .then(() => { state = 'saved'; setTimeout(() => { state = 'idle' }, 2200) })
            .catch(() => { state = 'error'; setTimeout(() => { state = 'idle' }, 2600) });
    "
    x-bind:disabled="state === 'loading'"
    x-bind:class="{ 'dz-confirm-saved': state === 'saved', 'dz-confirm-error': state === 'error' }"
    {{ $attributes->class(['dz-confirm-button']) }}
>
    <span x-show="state === 'idle'" x-cloak>{{ $label }}</span>
    <span x-show="state === 'loading'" x-cloak>{{ $loadingLabel }}</span>
    <span x-show="state === 'saved'" x-cloak>✓ {{ $savedLabel }}</span>
    <span x-show="state === 'error'" x-cloak>✗ {{ $errorLabel }}</span>
</button>
