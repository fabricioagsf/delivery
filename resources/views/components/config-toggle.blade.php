@props(['name', 'label', 'checked' => false])

<label class="caixa-marcar">
    <input type="checkbox" name="{{ $name }}" value="1" {{ $checked ? 'checked' : '' }}>
    {{ $label }}
</label>
