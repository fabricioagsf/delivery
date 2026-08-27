{{-- Menu lateral expansível: conta do cliente (dados, endereços, cartões, pedidos) --}}
<div class="drawer-velo oculto" id="drawer-velo"></div>

<aside class="drawer oculto" id="drawer-conta" aria-label="{{ texto('conta', 'titulo', 'Minha conta') }}">
    <div class="drawer__topo">
        <h2 id="drawer-titulo">{{ texto('conta', 'titulo', 'Minha conta') }}</h2>
        <button type="button" class="drawer__fechar" id="fechar-conta" aria-label="{{ texto('conta', 'fechar', 'Fechar') }}">&times;</button>
    </div>

    <div class="drawer__corpo" id="drawer-corpo">
        {{-- ================= PORTA (troca de senha obrigatória / completar cadastro) ================= --}}
        <div id="conta-porta" class="oculto">
            <div class="porta-aviso">
                <strong id="porta-titulo"></strong>
                <p id="porta-nota"></p>
            </div>

            {{-- Nova senha (obrigatória após reenvio 123Mudar) --}}
            <form id="form-porta-senha" class="painel-form" data-form="troca-senha" hidden>
                <label>{{ texto('conta', 'campo.nova_senha', 'Nova senha (mínimo 6 caracteres)') }}
                    <input type="password" name="senha" required minlength="6" autocomplete="new-password">
                </label>
                <label>{{ texto('conta', 'campo.confirmar_nova', 'Confirmar nova senha') }}
                    <input type="password" name="senha_confirmation" required minlength="6" autocomplete="new-password">
                </label>
                <p class="form-mensagem" hidden></p>
                <button type="submit" class="botao botao--chefe bloco">{{ texto('conta', 'botao.salvar_senha', 'Salvar e continuar') }}</button>
            </form>

            {{-- Completar cadastro (após login social) --}}
            <form id="form-porta-completar" class="painel-form" data-form="completar" hidden>
                <label>{{ texto('conta', 'campo.telefone', 'Telefone / WhatsApp') }}
                    <input type="tel" name="telefone" required autocomplete="tel">
                </label>

                <div class="destaque-chave">
                    <strong>{{ texto('conta', 'chave.titulo', 'Chave de segurança') }}</strong>
                    <p>{{ texto('conta', 'chave.explicacao', 'Você vai informar esta chave na entrega para confirmar que o pedido chegou até você.') }}</p>
                </div>
                <label>{{ texto('conta', 'campo.chave_seguranca', 'Crie sua chave (mínimo 4 caracteres)') }}
                    <input type="text" name="chave_seguranca" required minlength="4" maxlength="20" autocomplete="off">
                </label>
                <label>{{ texto('conta', 'campo.confirmar_chave', 'Confirme a chave') }}
                    <input type="text" name="chave_seguranca_confirmation" required minlength="4" maxlength="20" autocomplete="off">
                </label>
                <p class="form-mensagem" hidden></p>
                <button type="submit" class="botao botao--chefe bloco">{{ texto('conta', 'botao.completar', 'Concluir cadastro') }}</button>
            </form>
        </div>

        {{-- ================= VISITANTE ================= --}}
        <div id="conta-visitante" @if(auth('cliente')->check()) class="oculto" @endif>
            <div class="abas">
                <button type="button" class="aba ativa" data-aba="entrar">{{ texto('conta', 'aba.entrar', 'Entrar') }}</button>
                <button type="button" class="aba" data-aba="criar">{{ texto('conta', 'aba.criar', 'Criar conta') }}</button>
            </div>

            <form id="form-login" class="painel-form ativo" data-form="entrar">
                @csrf
                <label>{{ texto('conta', 'campo.email', 'E-mail') }}
                    <input type="email" name="email" required autocomplete="email">
                </label>
                <label>{{ texto('conta', 'campo.senha', 'Senha') }}
                    <input type="password" name="senha" required autocomplete="current-password">
                </label>
                <p class="form-mensagem" hidden></p>
                <button type="submit" class="botao botao--chefe">{{ texto('conta', 'botao.entrar', 'Entrar') }}</button>
            </form>

            @if(count(\App\Services\LoginSocial::ativos()) > 0)
                <div class="social-linha">
                    <span class="social-linha__ou">{{ texto('conta', 'social.ou', 'ou entre com') }}</span>
                    <div class="social-linha__botoes">
                        @foreach(\App\Services\LoginSocial::ativos() as $provedor)
                            <a class="botao-social botao-social--{{ $provedor }}" href="{{ route('cliente.social', $provedor) }}">
                                {{ texto('conta', 'social.'.$provedor, ucfirst($provedor)) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form id="form-registro" class="painel-form" data-form="criar" hidden>
                @csrf
                <label>{{ texto('conta', 'campo.nome', 'Nome completo') }}
                    <input type="text" name="nome" required minlength="3" autocomplete="name">
                </label>
                <label>{{ texto('conta', 'campo.telefone', 'Telefone / WhatsApp') }}
                    <input type="tel" name="telefone" required autocomplete="tel">
                </label>
                <label>{{ texto('conta', 'campo.email', 'E-mail') }}
                    <input type="email" name="email" required autocomplete="email">
                </label>
                <label>{{ texto('conta', 'campo.senha', 'Senha') }}
                    <input type="password" name="senha" required minlength="6" autocomplete="new-password">
                </label>
                <label>{{ texto('conta', 'campo.confirmar_senha', 'Confirmar senha') }}
                    <input type="password" name="senha_confirmation" required minlength="6" autocomplete="new-password">
                </label>

                <div class="destaque-chave">
                    <strong>{{ texto('conta', 'chave.titulo', 'Chave de segurança') }}</strong>
                    <p>{{ texto('conta', 'chave.explicacao', 'Você vai informar esta chave na entrega para confirmar que o pedido chegou até você.') }}</p>
                </div>
                <label>{{ texto('conta', 'campo.chave_seguranca', 'Crie sua chave (mínimo 4 caracteres)') }}
                    <input type="text" name="chave_seguranca" required minlength="4" maxlength="20" autocomplete="off">
                </label>
                <label>{{ texto('conta', 'campo.confirmar_chave', 'Confirme a chave') }}
                    <input type="text" name="chave_seguranca_confirmation" required minlength="4" maxlength="20" autocomplete="off">
                </label>

                <p class="form-mensagem" hidden></p>
                <button type="submit" class="botao botao--chefe">{{ texto('conta', 'botao.criar_conta', 'Criar minha conta') }}</button>
            </form>
        </div>

        {{-- ================= LOGADO ================= --}}
        <div id="conta-logada" @if(!auth('cliente')->check()) class="oculto" @endif>
            <p class="drawer__saudacao"></p>

            <div class="sanfona">
                <section class="sanfona__item">
                    <button type="button" class="sanfona__cabeca">{{ texto('conta', 'secao.dados', 'Dados pessoais') }}</button>
                    <div class="sanfona__corpo">
                        <form data-form="dados">
                            @csrf
                            <label>{{ texto('conta', 'campo.nome', 'Nome completo') }}<input type="text" name="nome" required></label>
                            <label>{{ texto('conta', 'campo.telefone', 'Telefone / WhatsApp') }}<input type="tel" name="telefone" required></label>
                            <label>{{ texto('conta', 'campo.email', 'E-mail') }}<input type="email" name="email" required></label>
                            <details class="troca-chave">
                                <summary>{{ texto('conta', 'chave.trocar', 'Trocar chave de segurança') }}</summary>
                                <label>{{ texto('conta', 'campo.chave_atual', 'Chave atual') }}
                                    <input type="password" name="chave_seguranca_atual" autocomplete="off">
                                </label>
                                <label>{{ texto('conta', 'campo.chave_nova', 'Nova chave') }}
                                    <input type="password" name="chave_seguranca_nova" minlength="4" maxlength="20" autocomplete="off">
                                </label>
                            </details>
                            <p class="form-mensagem" hidden></p>
                            <button type="submit" class="botao">{{ texto('conta', 'botao.salvar', 'Salvar') }}</button>
                        </form>
                    </div>
                </section>

                <section class="sanfona__item">
                    <button type="button" class="sanfona__cabeca">{{ texto('conta', 'secao.senha', 'Alterar senha') }}</button>
                    <div class="sanfona__corpo">
                        <form data-form="senha">
                            @csrf
                            <label>{{ texto('conta', 'campo.nova_senha', 'Nova senha (mínimo 6 caracteres)') }}
                                <input type="password" name="senha" required minlength="6" autocomplete="new-password">
                            </label>
                            <label>{{ texto('conta', 'campo.confirmar_nova', 'Confirmar nova senha') }}
                                <input type="password" name="senha_confirmation" required minlength="6" autocomplete="new-password">
                            </label>
                            <p class="form-mensagem" hidden></p>
                            <button type="submit" class="botao">{{ texto('conta', 'botao.salvar_senha', 'Salvar nova senha') }}</button>
                        </form>
                    </div>
                </section>

                <section class="sanfona__item">
                    <button type="button" class="sanfona__cabeca">{{ texto('conta', 'secao.enderecos', 'Endereços') }}</button>
                    <div class="sanfona__corpo">
                        <ul class="lista-registros" data-lista="enderecos"></ul>
                        <form data-form="endereco">
                            @csrf
                            <strong>{{ texto('conta', 'endereco.novo_titulo', 'Adicionar endereço') }}</strong>
                            <label>{{ texto('checkout', 'campo.rua', 'Rua') }}<input type="text" name="rua" required></label>
                            <div class="linha-dupla">
                                <label>{{ texto('checkout', 'campo.numero', 'Número') }}<input type="text" name="numero" required></label>
                                <label>{{ texto('checkout', 'campo.complemento', 'Complemento') }}<input type="text" name="complemento"></label>
                            </div>
                            <label>{{ texto('checkout', 'campo.bairro', 'Bairro') }}<input type="text" name="bairro" required></label>
                            <div class="linha-dupla">
                                <label>{{ texto('checkout', 'campo.cidade', 'Cidade') }}<input type="text" name="cidade" required></label>
                                <label>{{ texto('checkout', 'campo.cep', 'CEP') }}<input type="text" name="cep"></label>
                            </div>
                            <label class="caixa-marcar">
                                <input type="checkbox" name="principal" value="1">
                                {{ texto('conta', 'endereco.principal', 'Definir como principal') }}
                            </label>
                            <p class="form-mensagem" hidden></p>
                            <button type="submit" class="botao">{{ texto('conta', 'botao.salvar_endereco', 'Salvar endereço') }}</button>
                        </form>
                    </div>
                </section>

                <section class="sanfona__item">
                    <button type="button" class="sanfona__cabeca">{{ texto('conta', 'secao.cartoes', 'Cartões') }}</button>
                    <div class="sanfona__corpo">
                        <p class="nota-segura">{{ texto('conta', 'cartoes.nota_seguranca', 'Por segurança, guardamos apenas a bandeira e os 4 últimos dígitos do seu cartão.') }}</p>
                        <ul class="lista-registros" data-lista="cartoes"></ul>
                        <form data-form="cartao">
                            @csrf
                            <strong>{{ texto('conta', 'cartoes.novo_titulo', 'Adicionar cartão') }}</strong>
                            <label>{{ texto('conta', 'cartoes.campo.apelido', 'Apelido (ex.: Cartão do dia a dia)') }}<input type="text" name="apelido" required maxlength="80"></label>
                            <label>{{ texto('conta', 'cartoes.campo.numero', 'Número do cartão') }}<input type="text" name="numero" required inputmode="numeric" placeholder="0000 0000 0000 0000"></label>
                            <div class="linha-dupla">
                                <label>{{ texto('conta', 'cartoes.campo.validade', 'Validade (MM/AA)') }}<input type="text" name="validade" required placeholder="12/28"></label>
                                <label>{{ texto('conta', 'cartoes.campo.titular', 'Titular') }}<input type="text" name="titular" maxlength="150"></label>
                            </div>
                            <p class="form-mensagem" hidden></p>
                            <button type="submit" class="botao">{{ texto('conta', 'cartoes.botao.salvar', 'Salvar cartão') }}</button>
                        </form>
                    </div>
                </section>

                <section class="sanfona__item">
                    <button type="button" class="sanfona__cabeca">{{ texto('conta', 'secao.pedidos', 'Meus pedidos') }}</button>
                    <div class="sanfona__corpo">
                        <ul class="lista-pedidos" data-lista="pedidos"></ul>
                    </div>
                </section>
            </div>

            <button type="button" class="botao botao--fantasma" data-form="sair">{{ texto('conta', 'botao.sair', 'Sair da conta') }}</button>
        </div>
    </div>
</aside>
