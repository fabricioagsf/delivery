@props(['provider', 'description' => null])

@php
    $uri = route('authmulti.social.callback', $provider);
@endphp

<p class="nota-segura nota-segura--admin">
    @if($description)
        {!! str_replace(':callback', '<code>'.$uri.'</code>', $description) !!}
    @else
        Cadastre a URI de redirecionamento no console do provedor: <code>{{ $uri }}</code>
    @endif
</p>
