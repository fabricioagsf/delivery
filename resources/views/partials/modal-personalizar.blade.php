<div class="modal-personalizar" id="modal-personalizar" hidden>
    <div class="modal-personalizar__velo" data-fechar></div>
    <div class="modal-personalizar__janela" role="dialog" aria-modal="true" aria-labelledby="modal-personalizar-titulo">
        <button type="button" class="modal-personalizar__fechar" data-fechar aria-label="Fechar">&times;</button>

        <h2 class="modal-personalizar__titulo" id="modal-personalizar-titulo"></h2>
        <p class="modal-personalizar__base">...</p>

        <div class="modal-personalizar__corpo" id="modal-personalizar-corpo"></div>

        <div class="modal-personalizar__rodape">
            <div class="modal-personalizar__qtd">
                <button type="button" data-qtd="menos">−</button>
                <span data-qtd="valor">1</span>
                <button type="button" data-qtd="mais">+</button>
            </div>

            <div class="modal-personalizar__confirma">
                <strong data-total></strong>
                <button type="button" class="botao botao--chefe" data-confirmar>
                    {{ texto('vitrine', 'modal.confirmar', 'Adicionar ao carrinho') }}
                </button>
            </div>
        </div>
    </div>
</div>
