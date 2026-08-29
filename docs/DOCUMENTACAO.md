# Documentação técnica — Gostosuras

Mapa completo de rotas, controllers, services e helpers. Complementa o `README.md`.

> Stack: PHP 8.3 · Laravel 13.26 · MySQL · CSS puro · AJAX vanilla (sem frameworks).
> **Atenção (Laravel 13):** a forma de expressão `@php(...)` está PROIBIDA nas views —
> usar sempre bloco `@php ... @endphp;` (ver regra 13 da skill dev-php).

---

## 1. Rotas públicas (loja)

| Método | URI | Nome | Controller@ação | Função |
|---|---|---|---|---|
| GET | `/` | `vitrine` | VitrineController@index | Vitrine (filtro por categoria aceita AJAX e devolve partial `vitrine.partials.resultados`) |
| GET | `/vitrine/versao` | `vitrine.versao` | VitrineController@versao | Hash de preço/estoque para polling da vitrine viva |
| GET | `/cardapio` | `cardapio` | CardapioController@index | Cardápio digital público: produtos ativos por categorias ativas; pedido direto reusa o fluxo loja |
| GET | `/manifest.webmanifest` | `pwa.manifest` | PwaController@manifest | Web App Manifest do PWA (nome da loja, tema salmão, ícone SVG) |
| GET | `/sw.js` | `pwa.service_worker` | PwaController@serviceWorker | Service worker: pré-cacheia CSS/JS + imagens de produtos ativos e banners (cardápio offline). Cache nomeado por `pwa_cache_versao` |
| GET | `/carrinho` | `carrinho.index` | CarrinhoController@index | Página do carrinho |
| POST | `/carrinho/adicionar` | `carrinho.adicionar` | CarrinhoController@adicionar | Adiciona item (JSON: contagem, mensagem). Aceita `quantidade` e `complementos` (array de ids); cada combinação vira linha separada |
| POST | `/carrinho/atualizar` | `carrinho.atualizar` | CarrinhoController@atualizar | Muda quantidade (valida estoque) |
| POST | `/carrinho/remover` | `carrinho.remover` | CarrinhoController@remover | Remove item |
| GET | `/checkout` | `checkout` | CheckoutController@index | Formulário de checkout |
| POST | `/checkout` | `checkout.finalizar` | CheckoutController@store | Cria o pedido (transação + lockForUpdate de estoque) |
| POST | `/checkout/pontos` | `checkout.validar_pontos` | CheckoutController@validarPontos | Valida a troca de pontos por desconto (AJAX): módulo ativo + cliente logado + saldo; devolve `{desconto, mensagem, saldo}` |
| GET | `/pedido/{codigo}` | `pedido.confirmacao` | PedidoController@confirmacao | Confirmação com código, Pix e chave de segurança |
| POST | `/pedido/{codigo}/pagar` | `pedido.pagar` | PedidoController@pagar | Pagar de novo (Mercado Pago) um pedido pendente |
| POST | `/webhooks/mercadopago` | `webhook.mercadopago` | WebhookController@mercadopago | Notificação de pagamento MP (CSRF liberado) |
| POST | `/webhooks/efi` | `webhook.efi` | WebhookController@efi | Notificação Pix Efí (CSRF liberado) |

## 2. Rotas do cliente (`/cliente`, prefixo `cliente.`)

| Método | URI | Nome | Auth | Função |
|---|---|---|---|---|
| GET | `/cliente/csrf` | `cliente.csrf` | — | Token CSRF fresco para o AJAX do drawer |
| POST | `/cliente/registrar` | `cliente.registrar` | — | Cria conta (nome, telefone, e-mail, senha, chave) |
| POST | `/cliente/login` | `cliente.login` | — | Login; com a senha temporária `123Mudar` grava `session(trocar_senha_obrigatoria)` |
| POST | `/cliente/logout` | `cliente.logout` | cliente | Sai |
| GET | `/cliente/painel` | `cliente.painel` | cliente | JSON do drawer: dados, endereços, cartões, pedidos |
| PUT | `/cliente/dados` | `cliente.dados` | cliente | Atualiza dados + troca opcional da chave de segurança |
| POST | `/cliente/senha` | `cliente.senha` | cliente | Troca de senha (espontânea ou obrigatória); limpa o flag da porta |
| POST | `/cliente/completar` | `cliente.completar` | cliente | Completa cadastro pós-login social (telefone + chave); limpa `session(completar_cadastro)` |
| POST | `/cliente/enderecos` | `cliente.enderecos.store` | cliente | Novo endereço |
| DELETE | `/cliente/enderecos/{endereco}` | `cliente.enderecos.destroy` | cliente | Remove |
| PATCH | `/cliente/enderecos/{endereco}/principal` | `cliente.enderecos.principal` | cliente | Define principal |
| POST | `/cliente/cartoes` | `cliente.cartoes.store` | cliente | Salva cartão (só bandeira + 4 últimos dígitos) |
| DELETE | `/cliente/cartoes/{cartao}` | `cliente.cartoes.destroy` | cliente | Remove |
| GET | `/cliente/social/{provedor}` | `cliente.social` | — | Inicia OAuth (google / facebook / microsoft) |
| GET | `/cliente/social/{provedor}/callback` | `cliente.social.callback` | — | Callback OAuth: entra ou cria conta + porta do drawer |

**Fluxo da porta (obrigatório):** login social novo → `session(completar_cadastro)` →
drawer abre em `#conta-porta` pedindo telefone + chave. Login com `123Mudar` →
`session(trocar_senha_obrigatoria)` → drawer abre na troca de senha. O front lê
`window.ContaEstado` (injetado em `layouts/loja.blade.php`) e o `loja.js` exibe a porta;
os flags são limpos no servidor ao concluir (`ContaController`).

## 3. Rotas do painel (`/admin`, prefixo `admin.`, middleware `auth`)

| Método | URI | Nome | Controller | Função |
|---|---|---|---|---|
| GET/POST | `/admin/login` | `admin.login` / `admin.login.tentar` | Admin\AuthController | Acesso do painel (usuário/senha só no banco; `php artisan admin:senha`) |
| POST | `/admin/logout` | `admin.logout` | Admin\AuthController | Sai |
| GET | `/admin` | `admin.dashboard` | Admin\DashboardController | Métricas do dia/mês, gráfico, estoque crítico |
| GET/POST | `/admin/produtos…` | `admin.produtos.*` | Admin\ProdutoController | CRUD + estoque, ativo, destaque (POSTs simples) |
| GET | `/admin/pedidos` | `admin.pedidos.index` | Admin\PedidoController | Lista filtrável |
| GET | `/admin/pedidos/{pedido}` | `admin.pedidos.show` | Admin\PedidoController | Detalhe (chave de segurança, NF) |
| POST | `/admin/pedidos/{pedido}/status` | `admin.pedidos.status` | Admin\PedidoController | Status (cancelar devolve estoque) |
| POST | `/admin/pedidos/{pedido}/nota` | `admin.pedidos.nota` | Admin\PedidoController | Gera NF como PENDENTE (sped-nfe instalado) |
| POST | `/admin/pedidos/{pedido}/whatsapp` | `admin.pedidos.whatsapp` | Admin\PedidoController | Encaminha o pedido ao cliente pelo WhatsApp (API Cloud ou fallback link `wa.me`) |
| GET | `/admin/item-venda` | `admin.item-venda.index` | Admin\ItemVendaController | Painel de configuração do módulo produto/serviço (item-venda) |
| POST | `/admin/item-venda` | `admin.item-venda.atualizar` | Admin\ItemVendaController | Salva `item_venda_ativo`/`item_venda_tipo` |
| GET | `/admin/pwa` | `admin.pwa.index` | Admin\AdminPwaController | Painel do módulo PWA (status, qtd de imagens, links, renovar cache) |
| POST | `/admin/pwa` | `admin.pwa.atualizar` | Admin\AdminPwaController | Salva `pwa_ativo` e, marcado, incrementa `pwa_cache_versao` |
| GET/POST | `/admin/configuracoes` | `admin.configuracoes.index` / `.salvar` | Admin\ConfiguracaoController | 20 chaves da tabela `configuracoes` (loja, NF-e, WhatsApp, login social) |
| GET | `/admin/clientes` | `admin.clientes.index` | Admin\ClienteController | Contas + métricas |
| POST | `/admin/clientes/{cliente}/senha-whatsapp` | `admin.clientes.senha` | Admin\ClienteController | Redefine para `123Mudar` e envia (API ou link wa.me) |
| POST | `/admin/clientes/campanha` | `admin.clientes.campanha` | Admin\ClienteController | Oferta em massa (API direto ou links prontos) |
| GET | `/admin/relatorios` | `admin.relatorios` | Admin\RelatorioController | Abas: vendas, produtos, horários, pagamentos, entregas, estoque |
| GET | `/admin/relatorios/exportar` | `admin.relatorios.exportar` | Admin\RelatorioController | CSV da aba ativa (BOM UTF-8, separador `;`) |
| GET | `/admin/relatorios/mensal` | `admin.relatorios.mensal` | Admin\RelatorioController | Extrato mensal (PDF via impressão) |
| GET | `/admin/relatorios/simples` | `admin.relatorios.simples` | Admin\RelatorioController | Planilha vendas/produtos (`?tipo=`, `?export=csv`) |
| GET/POST | `/admin/banners…` | `admin.banners.*` | Admin\BannerController | CRUD + agendamento (entra/sai do ar sozinho) |
| GET | `/admin/auditoria` | `admin.auditoria.index` | Admin\AuditoriaController | Histórico do banco |
| GET | `/admin/auditoria/{log}` | `admin.auditoria.show` | Admin\AuditoriaController | Detalhe antigo × novo |
| POST | `/admin/auditoria/{log}/restaurar` | `admin.auditoria.restaurar` | Admin\AuditoriaController | Restauração por evento (exige `MASTER_SENHA` do `.env`) |
| GET | `/admin/cupons` | `admin.cupons.index` | Admin\CupomController | Lista/CRUD de cupons de desconto |
| GET/POST | `/admin/cupons/create` | `admin.cupons.create` / `.store` | Admin\CupomController | Novo cupom |
| GET/POST | `/admin/cupons/{cupom}/edit` | `admin.cupons.edit` / `.update` | Admin\CupomController | Editar cupom |
| POST | `/admin/cupons/{cupom}` | `admin.cupons.destroy` | Admin\CupomController | Excluir cupom |
| POST | `/admin/cupons/{cupom}/divulgar` | `admin.cupons.divulgar` | Admin\CupomController | Marca como promoção em destaque (`cupom_destaque`) |
| POST | `/admin/cupons/{cupom}/parar` | `admin.cupons.pararDivulgacao` | Admin\CupomController | Remove o destaque |
| GET | `/admin/fidelidade` | `admin.fidelidade.index` | Admin\FidelidadeController | Painel do programa de pontos (métricas + configuração) |
| POST | `/admin/fidelidade` | `admin.fidelidade.atualizar` | Admin\FidelidadeController | Salva `fidelidade_ativo`/`fidelidade_ganho`/`fidelidade_ponto_valor` |

## 4. Services (`app/Services`)

| Service | Papel |
|---|---|
| `Carrinho` | Carrinho em sessão; preços SEMPRE relidos do banco. Suporta `complementos` e `quantidade` — cada combinação de complementos vira uma linha separada (chave md5) |
| `LoginSocial` | OAuth 2.0 puro (Google/Facebook/Microsoft/Instagram, via auth-multi). `ativo()` exige `{provedor}_login_ativo='1'` + client_id + client_secret na tabela `configuracoes` |
| `WhatsApp` | Meta Cloud API (`graph.facebook.com/v21.0`). `disponivel()` exige `whatsapp_ativo='1'` + token + phone_id. `enviarTexto()` entrega a mensagem; `linkWhatsapp()` gera o link `wa.me`. Sem API, o painel cai no modo link `wa.me` automaticamente. Usado para: ofertas/senhas de clientes e encaminhamento de pedidos (admin) |
| `MercadoPago` | Checkout Pro (redirecionamento — nenhum dado de cartão no servidor). `criarPreferencia()` → URL do MP; `consultarPorReferencia()`/`buscarPagamento()` para confirmação. Credenciais: `mercadopago_ativo` + `mercadopago_access_token` |
| `Efi` | Pix API v2 (copia e cola). `criarCobranca()` idempotente (txid = código do pedido); `consultarCobranca()` para status. Credenciais: `efi_ativo` + client id/secret + `efi_pix_chave` (+ `efi_sandbox`). Pix na Efí é gratuito |
| `ItemVenda` (lib `fabricioagsf/item-venda`) | Módulo produto/serviço; tela `/admin/item-venda` controla `item_venda_ativo` (1/0) e `item_venda_tipo` (`produtos`/`servicos`/`ambos`) |
| `Cupom` (`app/Support/Cupom.php`) | Valida cupons de desconto (percentual/fixo): `resolver()` acha o cupom por código, `validar()` aplica as restrições (ativo, vigência, limite de uso, valor mínimo), `calcularDesconto()`/`aplicar` abatem do subtotal, `registrarUso()` incrementa o contador `usos` (na confirmação do pedido). `promo_destaque()` (helper) devolve o cupom ativo+vigente apontado por `cupom_destaque` ou `null`. |
| `Fidelidade` (`app/Support/Fidelidade.php`) | Programa de pontos: `ativo()` (chave `fidelidade_ativo`), `ganho()` (pontos por R$ 1,00), `pontoValor()` (R$ por ponto), `pontosParaPedido($subtotal)` (base arredondada p/ baixo), `pontosParaValor()`/`descontoDePontos()`/`descontoMaximo()` (nunca passa do subtotal) e `saldoDoCliente()`. Usado no checkout (`validarPontos` AJAX + `store`) e na confirmação do pedido. Uso no pedido: pontos creditados e resgatados na mesma transação do pedido. |

## 5. Helpers (`app/Support/helpers.php`)

`texto(pagina, chave, fallback)` — textos da tabela `textos` (resiliente a banco fora do ar) ·
`config_loja(chave, fallback)` — tabela `configuracoes` · `preco_br` · `status_pedido` ·
`forma_pagamento_label` · `detectar_bandeira` · `promo_destaque()` — cupom em destaque.

## 6. Chaves da tabela `configuracoes`

`taxa_entrega`, `chave_pix`, `margem_producao`, `emitir_nfe`, `empresa_cnpj`,
`empresa_razao_social`, `empresa_inscricao_estadual`, `nfe_ambiente`,
`whatsapp_ativo`, `whatsapp_token`, `whatsapp_phone_id`,
`google_login_ativo`, `google_client_id`, `google_client_secret`,
`facebook_login_ativo`, `facebook_client_id`, `facebook_client_secret`,
`microsoft_login_ativo`, `microsoft_client_id`, `microsoft_client_secret`,
`instagram_login_ativo`, `instagram_client_id`, `instagram_client_secret`,
`mercadopago_ativo`, `mercadopago_access_token`, `mercadopago_public_key`,
`efi_ativo`, `efi_client_id`, `efi_client_secret`, `efi_pix_chave`, `efi_sandbox`,
`item_venda_ativo` ('1' = módulo produto/serviço ligado), `item_venda_tipo`
(`produtos` | `servicos` | `ambos`), `pwa_ativo` ('1' = service worker registrado →
cardápio offline), `pwa_cache_versao` (número do cache; aumentar força recarga),
`tema_loja` (`guloseimas` | `italiana` | `japonesa` | `chinesa` | `mexicana`),
`cupom_destaque` (código do cupom ativo divulgado como promoção em destaque),
`fidelidade_ativo` ('1' = programa de pontos ligado), `fidelidade_ganho` (pontos por
R$ 1,00 de compra, padrão 1), `fidelidade_ponto_valor` (R$ de cada ponto no resgate,
padrão 0.10).

Padrões criados por `ConfiguracaoSeeder`. Editáveis em **/admin/configuracoes**.

## 7. Front-end

- `public/js/loja.js` — vitrine, carrinho, drawer da conta, porta pós-social, checkout com **validação de cupom** (AJAX puro; retry automático em 419).
- `public/js/admin.js` + scripts inline por tela — produtos, pedidos, clientes (senha/campanha respeitam `modo: api|link`), banners.
- `layouts/loja.blade.php` injeta `window.Rotas`, `window.Textos` e `window.ContaEstado`.

## 8. Temas (identidade cultural)

- Tema = **identidade cultural** (cores + nome + slogan + rodapé + herói + título do
  navegador). O **cardápio/conteúdo** é gerido por quem cadastra os produtos (a
  regionalidade de conteúdo fica a cargo das **filiais/multi-lojas**).
- `App\Support\Temas` é o registro dos temas (`guloseimas`, `italiana`, `japonesa`,
  `chinesa`, `mexicana`) com o caminho da CSS de paleta. `config_loja('tema_loja')` define
  o ativo (selecionado em `/admin/configuracoes` → "Tema da loja").
- Helpers (helpers.php): `tema_ativo()`, `tema_css()`, `tema_texto('chave', fallback)`.
- `tema_texto()` lê a identidade no grupo `tema` da tabela `textos` (chaves
  `{tema}.nome|slogan|sobre|direitos|hero|hero_sub|hero_botao`).
- Paletas: `guloseimas` usa as cores base (sem override); os demais têm
  `public/css/themes/{italiana,japonesa,chinesa,mexicana}.css`, carregados em
  `layouts/loja.blade.php` após `loja.css`.

## 9. PWA (app / cardápio offline)

- Implementação **100% JS puro** (Service Worker), sem dependência externa.
- `PwaController@serviceWorker` gera `/sw.js` (infere assets + imagens de **produtos
  ativos** e **banners** do banco — seleção dinâmica). `PwaController@manifest` gera
  `/manifest.webmanifest` (nome/slogan do **tema ativo**, ícone SVG).
- `resources/views/pwa/sw.blade.php` é o service worker:
  - `install` pré-cacheia CSS/JS do cardápio + imagens (cache nomeado por `pwa_cache_versao`);
  - `activate` descarta caches de versões antigas;
  - navegação `network-first` (sem internet cai na última cópia do cardápio); assets
    estáticos cache-first com atualização em segundo plano; `POST`/checkout não interceptados.
- `layouts/loja.blade.php` registra o SW quando `pwa_ativo='1'`, expõe o manifesto e um
  botão **"Instalar app"** (mostra só quando o navegador permite, evento `beforeinstallprompt`).
- Gestão em `/admin/pwa` (menu "PWA / App"): ativar/desativar, ver nº de imagens e
  links, e **renovar o cache** (incrementa `pwa_cache_versao`).

## 10. Complementos (dados)

- Migrations: `2026_08_27_000001_create_produto_complementos_table.php` (tabela
  `produto_complementos`) e `2026_08_27_000002_add_complementos_to_pedido_itens_table.php`
  (coluna JSON `complementos` em `pedido_itens`).
- Model `ProdutoComplemento` (`TIPO adicional|remocao`): colunas `produto_id`, `tipo`,
  `nome`, `preco`, `ativo`, `ordem`. Métodos `ehAdicional()`/`ehRemocao()`.
- `Produto` e `PedidoItem` têm relação `complementos`; `Produto::complementosAtivos()`.
- Cartão/checkout por linhas: cada combinação de complementos gera linha separada;
  preços recomputados do banco (regra de ouro). O pedido guarda snapshot dos complementos.

## 11. Multi-lojas (redes de franquia)

### Conceito

Cada loja (tenant) da rede tem seus próprios **pedidos, cupons, banners, configurações e textos**, mas **compartilha o catálogo de produtos** (produtos e categorias). O estoque é **individual por loja**.

| Entidade | Compartilhado? | Estoque/Controlado por loja? |
|---|---|---|
| Produto | ✓ Global | ✗ |
| Categoria | ✓ Global | ✗ |
| ProdutoEstoque | ✗ | ✓ Por loja |
| Pedido | ✗ | ✓ Por loja |
| Cupom | ✗ | ✓ Por loja |
| Banner | ✗ | ✓ Por loja |
| Configuração | ✗ | ✓ Per loja com fallback global |
| Texto | ✗ | ✓ Per loja com fallback global |
| Cliente | ✓ Global (fidelidade/shared) | ✗ |

### Arquitetura

- **Tabela `tenants`**: lojas da rede (da lib `auth-multi`).
- **`PossuiLoja` trait**: aplica automaticamente `loja_id` na criação + `LojaScope` global para filtrar queries.
- **`LojaScope`** (`app/Support/LojaScope.php`): filtra `WHERE loja_id = ?` nas queries Eloquent.
- **`semLoja()` scope**: remove o global scope para consultas cross-loja (ex.: webhooks).
- **Middleware `GarantirLojaAtiva`**: toda requisição web é garantida de ter `loja_id` na sessão. Se não tiver, fixa a primeira loja ativa automaticamente.
- **`helpers.php`**: `loja_atual()`, `loja_atual_id()`, `lojas_ativas()`.
- **`texto()` / `config_loja()`**: priorizam valor da loja ativa; sem ele, usam fallback global (`loja_id IS NULL`).

### Models com loja_id (PossuiLoja)

| Model | Tabela |
|---|---|
| `Pedido` | `pedidos` |
| `Cupom` | `cupons` |
| `Banner` | `banners` |
| `Configuracao` | `configuracoes` |
| `Texto` | `textos` |

### Modelo de estoque: `produto_estoques`

```
produto_estoques(id, produto_id, loja_id, estoque, estoque_minimo)
- Unique: (produto_id, loja_id)
- Estoque da loja = 0/null → produto indisponível na vitrine daquela loja
- Migration: 2026_08_28_000004_estoque_por_loja.php
```

**Criação/edição de produto**: admin seta estoque da loja ativa. Para criar estoque em outra loja, troca a loja no seletor do painel e edita novamente.

### Fluxo de vitrine (público)

1. `GarantirLojaAtiva` middleware fixa `loja_id` na sessão (se vazia).
2. `VitrineController`: carrega produtos ativos com `estoques` filtrados por `loja_id` da sessão.
3. `produto->temEstoque()` e `produto->esgotado()` consultam `produto_estoques` da loja ativa.
4. Checkout decrementa estoque em `produto_estoques` (não em `produtos`).

### Troca de loja

- **Público**: `POST /loja/trocar` → `LojaController@trocar` → `session(['loja_id' => $id])`.
- **Admin**: `POST /admin/lojas/trocar` → `LojasController@trocar` → `session(['loja_id' => $id])`.
- Seletores em `layouts/loja.blade.php` e `layouts/admin.blade.php`.

### Queries que atravessam lojas

- **Webhooks**: `Pedido::semLoja()->where(...)` — não filtra por loja.
- **Admin LojasController**: `Pedido::semLoja()->where('loja_id', $loja->id)->count()`.
- **Fidelidade (pontos)**: `Cliente` é global; pontos pertencem ao cliente (não à loja).

### Migrations

| Migration | O que faz |
|---|---|
| `2026_08_28_000003` | Adiciona `loja_id` em `produtos`, `categorias`, `pedidos`, `cupons`, `banners`, `configuracoes`, `textos` + índices únicos compostos |
| `2026_08_28_000004` | Cria `produto_estoques`; move estoque/minimo de `produtos`; remove `loja_id/estoque/estoque_minimo` de `produtos` |
| `2026_08_28_000005` | Remove `loja_id` de `categorias` (catálogo global) |

### Importante

- Ao criar produto pelo admin, o estoque é salvo para a **loja ativa no momento**. Para cada loja, o estoque deve ser configurado individualmente.
- Sem `loja_id` na sessão, o sistema usa a primeira loja ativa como padrão (nunca mostra "tudo").
- O campo `produto.preco` é **global** (mesmo valor em todas as lojas).
- O campo `categoria` é **global** (mesmo catálogo em todas as lojas).

## 12. Observações

- `resources/views/welcome.blade.php` é o scaffold padrão do Laravel, **não é renderizado**
  (a `/` é a vitrine) e cita rotas `login`/`register` inexistentes — inofensivo; pode ser
  removido se desejar.
- Auditoria em duas camadas (gatilhos MySQL + trait `Auditoravel`); comandos:
  `auditoria:sincronizar`, `auditoria:ver`, `auditoria:restaurar`.
- Hospedagem InfinityFree: se `CREATE TRIGGER` for bloqueado, a camada da aplicação
  (trait) segue funcionando sozinha.
