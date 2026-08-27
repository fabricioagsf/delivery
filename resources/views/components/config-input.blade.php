@props(['name', 'label', 'type' => 'text', 'value' => ''])

<label>{{ $label }}
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" autocomplete="off">
</label>
