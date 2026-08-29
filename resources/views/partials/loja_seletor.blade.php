@if(mostrar_seletor_loja())
<form method="POST" action="{{ route('loja.trocar') }}" class="loja-seletor" onsubmit="return confirm('{{ texto('loja', 'confirm.trocar', 'Trocando de loja recarrega a página e atualiza preços e disponibilidade. Continuar?') }}');">
    @csrf
    <label class="loja-seletor__rotulo">{{ texto('layout', 'loja.seletor', 'Loja') }}</label>
    <select name="loja_id" class="loja-seletor__select" onchange="this.form.submit()">
        @foreach($lojasAtivas as $loja)
            <option value="{{ $loja->id }}" {{ loja_atual()?->id === $loja->id ? 'selected' : '' }}>
                {{ $loja->nome }}
            </option>
        @endforeach
    </select>
</form>
@endif