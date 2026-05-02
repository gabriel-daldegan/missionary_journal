@props([
    'label' => false,
    'id' => false,
    'name' => '',
    'type' => 'text',
    'value' => false,
    'placeholder' => false,
    'labelClass' => 'text-gray-900',
    'inputClass' => 'text-gray-900 bg-primary-50',
    'required' => false,
    'autofocus' => false,
    'autocomplete' => false,
    'maxWidth' => 'max-w-xs',
    'disabled' => false,
])

@php
    $required = $required ? 'required' : '';
    $autofocus = $autofocus ? 'autofocus' : '';
    $value = $value ? 'value="' . $value . '"' : '';
    $autocomplete = $autocomplete ? 'autocomplete="' . $autocomplete . '"' : '';
    $disabled = $disabled ? 'disabled' : '';
@endphp

<fieldset {{ $attributes->merge(['class' => 'fieldset ' . $maxWidth]) }}>
    @if($label)
        <legend class="fieldset-legend font-medium">{{ $label }}</legend>
    @endif
    <input type="{{$type}}" class="input w-full" placeholder="{{$placeholder}}" name="{{$name}}" {{$required}} {{$autofocus}} {!! $value !!} {!! $autocomplete !!} {{$disabled}}  id="{{$id}}" {{ $attributes }}/>
</fieldset>
