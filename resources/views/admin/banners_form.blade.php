@extends('layouts.admin')

@section('titulo', texto('admin_banners', 'titulo.form', 'Banner — Gostosuras'))
@section('titulo_pagina', $banner->exists ? texto('admin_banners', 'form.titulo_editar', 'Editar banner') : texto('admin_banners', 'form.titulo_novo', 'Novo banner'))

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
          action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}">
        @csrf
        @if($banner->exists)
            @method('POST')
        @endif

        <fieldset class="secao-form">
            <legend>{{ texto('admin_banners', 'form.secao.imagem', 'Imagem') }}</legend>

            <label class="upload-banner" for="entrada-imagem">
                <input type="file" name="imagem" id="entrada-imagem" accept="image/jpeg,image/png,image/webp">
                <span class="upload-banner__icone" aria-hidden="true">🖼️</span>
                <span class="upload-banner__titulo">{{ $banner->exists ? texto('admin_banners', 'campo.trocar_imagem', 'Trocar imagem') : texto('admin_banners', 'campo.escolher_imagem', 'Escolher imagem') }}</span>
                <span class="upload-banner__arquivo" id="nome-arquivo">{{ texto('admin_banners', 'campo.sem_arquivo', 'Nenhuma imagem escolhida') }}</span>
                <span class="upload-banner__dica">{{ texto('admin_banners', 'novo.dica', 'Imagem panorâmica funciona melhor (ex.: 1600×600).') }}</span>
            </label>

            @if($banner->imagem)
                <figure class="pre-visualizacao-bloco" id="pre-visualizacao-atual">
                    <figcaption>{{ texto('admin_banners', 'campo.imagem_atual', 'Imagem atual') }}</figcaption>
                    <img src="{{ asset($banner->imagem) }}" class="pre-visualizacao pre-visualizacao--larga" alt="">
                </figure>
            @endif

            <img id="pre-visualizacao-nova" class="pre-visualizacao pre-visualizacao--larga escondido" alt="">
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_banners', 'form.secao.identificacao', 'Identificação') }}</legend>

            <label>{{ texto('admin_banners', 'campo.titulo', 'Título interno (opcional)') }}
                <input type="text" name="titulo" value="{{ old('titulo', $banner->titulo) }}" maxlength="120" placeholder="{{ texto('admin_banners', 'campo.titulo_placeholder', 'Ex.: Promoção Páscoa') }}">
            </label>

            <label>{{ texto('admin_banners', 'campo.link', 'Link ao clicar (opcional)') }}
                <input type="text" name="link" value="{{ old('link', $banner->link) }}" placeholder="/?categoria=brigadeiros">
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_banners', 'form.secao.agendamento', 'Agendamento') }}</legend>

            <p class="nota-segura nota-segura--admin">{{ texto('admin_banners', 'nota.agendamento', 'O banner entra no ar na data de início e sai na data de fim automaticamente. Deixe as datas vazias para ficar sempre no ar (enquanto estiver ligado).') }}</p>

            <div class="grade-2">
                <label>{{ texto('admin_banners', 'campo.inicio', 'Entrar no ar em') }}
                    <input type="datetime-local" name="inicio_em" value="{{ old('inicio_em', $banner->inicio_em?->format('Y-m-d\TH:i')) }}">
                </label>
                <label>{{ texto('admin_banners', 'campo.fim', 'Sair do ar em') }}
                    <input type="datetime-local" name="fim_em" value="{{ old('fim_em', $banner->fim_em?->format('Y-m-d\TH:i')) }}">
                </label>
            </div>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_banners', 'form.secao.exibicao', 'Exibição') }}</legend>

            <div class="grade-2 grade-2--exibicao">
                <label>{{ texto('admin_banners', 'campo.ordem', 'Ordem de exibição') }}
                    <input type="number" name="ordem" value="{{ old('ordem', $banner->ordem ?? 0) }}" min="0" max="999">
                </label>

                <label class="interruptor-linha">
                    <input type="checkbox" class="interruptor-caixa" name="ativo" value="1" id="interruptor-ativo" @checked(old('ativo', $banner->ativo ?? true))>
                    <span class="interruptor" aria-hidden="true"></span>
                    <span>{{ texto('admin_banners', 'campo.ativo', 'Banner ligado') }}</span>
                </label>
            </div>
        </fieldset>

        <div class="rodape-form">
            <button type="submit" class="botao botao--chefe">{{ texto('admin_banners', 'botao.salvar', 'Salvar banner') }}</button>
            <a href="{{ route('admin.banners.index') }}" class="botao">{{ texto('admin_banners', 'botao.cancelar', 'Cancelar') }}</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
    <script>
        (function () {
            var entrada = document.getElementById('entrada-imagem');
            var nome = document.getElementById('nome-arquivo');
            var preview = document.getElementById('pre-visualizacao-nova');
            if (!entrada) return;

            entrada.addEventListener('change', function () {
                var arquivo = entrada.files[0];
                if (!arquivo) return;

                nome.textContent = arquivo.name;
                preview.classList.remove('escondido');
                preview.src = URL.createObjectURL(arquivo);

                var atual = document.getElementById('pre-visualizacao-atual');
                if (atual) atual.classList.add('escondido');
            });
        })();
    </script>
@endpush
