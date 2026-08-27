@props(['legend', 'description' => null])

<fieldset class="secao-form">
    <legend>{{ $legend }}</legend>

    @if($description)
        <p class="nota-segura nota-segura--admin">{!! $description !!}</p>
    @endif

    {{ $slot }}
</fieldset>
