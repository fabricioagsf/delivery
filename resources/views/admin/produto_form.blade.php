@extends('layouts.admin')

@section('titulo', texto('admin_produtos', 'titulo.form', 'Produto — Gostosuras'))
@section('titulo_pagina', $produto->exists ? texto('admin_produtos', 'form.titulo_editar', 'Editar produto') : texto('admin_produtos', 'form.titulo_novo', 'Novo produto'))

@section('conteudo')
<section class="painel-admin painel-admin--estreito">
    @if($errors->any())
        <div class="alerta alerta--erro">
            <ul>
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" class="form-admin"
          action="{{ $produto->exists ? route('admin.produtos.update', $produto) : route('admin.produtos.store') }}">
        @csrf

        <fieldset class="secao-form">
            <legend>{{ texto('admin_produtos', 'form.secao.identificacao', 'Identificação') }}</legend>

            <label>{{ texto('admin_produtos', 'form.campo.nome', 'Nome do produto') }}
                <input type="text" name="nome" value="{{ old('nome', $produto->nome) }}" required minlength="2" maxlength="150"
                       placeholder="{{ texto('admin_produtos', 'form.exemplo.nome', 'Ex.: Brigadeiro de Pistache') }}">
            </label>

            <div class="grade-2">
                <label>{{ texto('admin_produtos', 'form.campo.categoria', 'Categoria') }}
                    <select name="categoria_id" required>
                        <option value="">{{ texto('admin_produtos', 'form.selecione', 'Selecione...') }}</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('categoria_id', $produto->categoria_id) == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nome }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>{{ texto('admin_produtos', 'form.campo.preco', 'Preço (R$)') }}
                    <input type="number" name="preco" step="0.01" min="0" value="{{ old('preco', $produto->preco) }}" required>
                </label>
            </div>

            <label>{{ texto('admin_produtos', 'form.campo.descricao', 'Descrição — aparece no card da vitrine') }}
                <textarea name="descricao" rows="3" maxlength="1000"
                          placeholder="{{ texto('admin_produtos', 'form.exemplo.descricao', 'Ex.: Massa cremosa de pistache com cobertura crocante de chocolate branco.') }}">{{ old('descricao', $produto->descricao) }}</textarea>
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_produtos', 'form.secao.imagem', 'Imagem do card') }}</legend>

            @if($produto->imagem)
                <img src="{{ asset($produto->imagem) }}" class="pre-visualizacao" alt="">
            @endif

            <label>{{ texto('admin_produtos', 'form.campo.imagem', $produto->exists ? texto('admin_produtos', 'form.trocar_imagem', 'Trocar imagem (opcional)') : texto('admin_produtos', 'form.campo.imagem', 'Imagem')) }}
                <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp">
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_produtos', 'form.secao.estoque', 'Preço e estoque') }}</legend>

            <p class="texto-suave">{{ texto('admin_produtos', 'form.campo.loja_aviso', 'Estoque é controlado por loja. Os valores abaixo valem para a loja ativa no painel — troque a loja no topo para editar o estoque de outra unidade.') }}</p>

            <div class="grade-2">
                <label>{{ texto('admin_produtos', 'form.campo.estoque', 'Quantidade em estoque (obrigatória para vender)') }}
                    <input type="number" name="estoque" min="0" value="{{ old('estoque', $produto->estoqueNaLoja()?->estoque) }}">
                </label>
                <label>{{ texto('admin_produtos', 'form.campo.minimo', 'Estoque mínimo (alerta)') }}
                    <input type="number" name="estoque_minimo" min="0" value="{{ old('estoque_minimo', $produto->estoqueNaLoja()?->estoque_minimo ?? 5) }}">
                </label>
            </div>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_produtos', 'form.secao.complementos', 'Personalizações (opcional)') }}</legend>

            <p class="form-nota">{{ texto('admin_produtos', 'form.nota.complementos', 'Deixe o cliente escolher adicionais (pagos) ou remoções (grátis) — ex.: cobertura +R$2,00 ou "sem leite condensado".') }}</p>

            <div id="lista-complementos" class="lista-complementos"
                 data-texto-tipo="{{ texto('admin_produtos', 'form.comp_tipo', 'Tipo') }}"
                 data-texto-adicional="{{ texto('admin_produtos', 'form.comp_adicional', 'Adicional (pago)') }}"
                 data-texto-remocao="{{ texto('admin_produtos', 'form.comp_remocao', 'Remoção (grátis)') }}"
                 data-texto-nome="{{ texto('admin_produtos', 'form.comp_nome', 'Nome') }}"
                 data-texto-preco="{{ texto('admin_produtos', 'form.comp_preco', 'Preço (R$)') }}"
                 data-texto-exemplo="{{ texto('admin_produtos', 'form.comp_exemplo', 'Ex.: Cobertura de chocolate') }}"
                 data-texto-remover="{{ texto('admin_produtos', 'form.remover_complemento', 'Remover') }}">
                @php
                    $complementosForm = old('complementos')
                        ? collect(old('complementos'))->values()
                        : $produto->complementos;

                    // Garante ao menos uma linha vazia como modelo (produto novo) para
                    // o botão "Adicionar personalização" clonar. Linhas com nome vazio
                    // são ignoradas no save — nenhum dado falso é criado.
                    if ($complementosForm->isEmpty()) {
                        $complementosForm = collect([[ 'id' => '', 'tipo' => 'adicional', 'nome' => '', 'preco' => '', 'ordem' => 0 ]]);
                    }
                @endphp
                @foreach($complementosForm as $indice => $comp)
                    @include('admin.partials.complemento_linha', [
                        'indice' => $indice,
                        'comp' => (object) $comp,
                    ])
                @endforeach
            </div>

            <button type="button" class="botao" id="adicionar-complemento">
                {{ texto('admin_produtos', 'form.botao_adicionar_complemento', '+ Adicionar personalização') }}
            </button>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_produtos', 'form.secao.exibicao', 'Exibição na vitrine') }}</legend>

            <label class="caixa-marcar">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $produto->ativo ?? true) ? 'checked' : '' }}>
                {{ texto('admin_produtos', 'form.campo.ativo', 'Visível na vitrine') }}
            </label>
            <label class="caixa-marcar">
                <input type="checkbox" name="destaque" value="1" {{ old('destaque', $produto->destaque) ? 'checked' : '' }}>
                {{ texto('admin_produtos', 'form.campo.destaque', 'Aparece nos Destaques da vitrine') }}
            </label>
        </fieldset>

        <div class="rodape-form">
            <button type="submit" class="botao botao--chefe">{{ texto('admin_produtos', 'form.botao.salvar', 'Salvar produto') }}</button>
            <a href="{{ route('admin.produtos.index') }}" class="link-voltar">{{ texto('confirmacao', 'botao.voltar', 'Voltar') }}</a>
        </div>
    </form>
</section>
@endsection
