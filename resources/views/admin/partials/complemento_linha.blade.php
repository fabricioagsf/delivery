@php
    $tipo = $comp->tipo ?? 'adicional';
    $nome = $comp->nome ?? '';
    $preco = $comp->preco ?? '';
    $id = $comp->id ?? '';
    $ordem = $comp->ordem ?? ($indice * 10);
@endphp
<div class="linha-complemento" data-linha>
    <input type="hidden" name="complementos[{{ $indice }}][id]" value="{{ old("complementos.$indice.id", $id) }}">
    <input type="hidden" name="complementos[{{ $indice }}][ordem]" value="{{ old("complementos.$indice.ordem", $ordem) }}">

    <label class="linha-complemento__campo">
        <span class="rotulo-mini">{{ texto('admin_produtos', 'form.comp_tipo', 'Tipo') }}</span>
        <select name="complementos[{{ $indice }}][tipo]" data-campo="tipo">
            <option value="adicional" {{ $tipo === 'adicional' ? 'selected' : '' }}>{{ texto('admin_produtos', 'form.comp_adicional', 'Adicional (pago)') }}</option>
            <option value="remocao" {{ $tipo === 'remocao' ? 'selected' : '' }}>{{ texto('admin_produtos', 'form.comp_remocao', 'Remoção (grátis)') }}</option>
        </select>
    </label>

    <label class="linha-complemento__campo linha-complemento__campo--nome">
        <span class="rotulo-mini">{{ texto('admin_produtos', 'form.comp_nome', 'Nome') }}</span>
        <input type="text" name="complementos[{{ $indice }}][nome]" maxlength="120"
               value="{{ old("complementos.$indice.nome", $nome) }}"
               placeholder="{{ texto('admin_produtos', 'form.comp_exemplo', 'Ex.: Cobertura de chocolate') }}">
    </label>

    <label class="linha-complemento__campo linha-complemento__campo--preco" data-campo-preco>
        <span class="rotulo-mini">{{ texto('admin_produtos', 'form.comp_preco', 'Preço (R$)') }}</span>
        <input type="number" name="complementos[{{ $indice }}][preco]" step="0.01" min="0"
               value="{{ old("complementos.$indice.preco", $tipo === 'remocao' ? '0' : $preco) }}"
               {{ $tipo === 'remocao' ? 'disabled' : '' }}>
    </label>

    <button type="button" class="botao-remover-linha" data-remover aria-label="{{ texto('admin_produtos', 'form.remover_complemento', 'Remover') }}">&times;</button>
</div>
