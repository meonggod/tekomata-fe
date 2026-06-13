@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'step' => null,
])

{{-- A compact labelled input for the internal config forms. Old-input aware so a
     failed submit keeps what the operator typed. --}}
<div>
    <label for="{{ $name }}" class="block text-xs font-medium text-gray-600">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if ($step !== null) step="{{ $step }}" @endif
           @if ($type === 'number') min="0" @endif
           value="{{ old($name, $value) }}"
           {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 '.($errors->has($name) ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40')]) }}>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
