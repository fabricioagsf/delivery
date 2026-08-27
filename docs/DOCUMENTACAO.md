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
| GET | `/carrinho` | `carrinho.index` | CarrinhoController@index | Página do carrinho |
| POST | `/carrinho/adicionar` | `carrinho.adicionar` | CarrinhoController@adicionar | Adiciona item (JSON: contagem, mensagem) |
| POST | `/carrinho/atualizar` | `carrinho.atualizar` | CarrinhoController@atualizar | Muda quantidade (valida estoque) |
| POST | `/carrinho/remover` | `carrinho.remover` | CarrinhoController@remover | Remove item |
| GET | `/checkout` | `checkout` | CheckoutController@index | Formulário de checkout |
| POST | `/checkout` | `checkout.finalizar` | CheckoutController@store | Cria o pedido (transação + lockForUpdate de estoque) |
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

## 4. Services (`app/Services`)

| Service | Papel |
|---|---|
| `Carrinho` | Carrinho em sessão; preços SEMPRE relidos do banco |
| `LoginSocial` | OAuth 2.0 puro (Google/Facebook/Microsoft). `ativo()` exige `{provedor}_login_ativo='1'` + client_id + client_secret na tabela `configuracoes` |
| `WhatsApp` | Meta Cloud API (`graph.facebook.com/v21.0`). `disponivel()` exige `whatsapp_ativo='1'` + token + phone_id. Sem API, o painel cai no modo link `wa.me` automaticamente |
| `MercadoPago` | Checkout Pro (redirecionamento — nenhum dado de cartão no servidor). `criarPreferencia()` → URL do MP; `consultarPorReferencia()`/`buscarPagamento()` para confirmação. Credenciais: `mercadopago_ativo` + `mercadopago_access_token` |
| `Efi` | Pix API v2 (copia e cola). `criarCobranca()` idempotente (txid = código do pedido); `consultarCobranca()` para status. Credenciais: `efi_ativo` + client id/secret + `efi_pix_chave` (+ `efi_sandbox`). Pix na Efí é gratuito |

## 5. Helpers (`app/Support/helpers.php`)

`texto(pagina, chave, fallback)` — textos da tabela `textos` (resiliente a banco fora do ar) ·
`config_loja(chave, fallback)` — tabela `configuracoes` · `preco_br` · `status_pedido` ·
`forma_pagamento_label` · `detectar_bandeira`.

## 6. Chaves da tabela `configuracoes`

`taxa_entrega`, `chave_pix`, `margem_producao`, `emitir_nfe`, `empresa_cnpj`,
`empresa_razao_social`, `empresa_inscricao_estadual`, `nfe_ambiente`,
`whatsapp_ativo`, `whatsapp_token`, `whatsapp_phone_id`,
`google_login_ativo`, `google_client_id`, `google_client_secret`,
`facebook_login_ativo`, `facebook_client_id`, `facebook_client_secret`,
`microsoft_login_ativo`, `microsoft_client_id`, `microsoft_client_secret`,
`mercadopago_ativo`, `mercadopago_access_token`, `mercadopago_public_key`,
`efi_ativo`, `efi_client_id`, `efi_client_secret`, `efi_pix_chave`, `efi_sandbox`.

Padrões criados por `ConfiguracaoSeeder`. Editáveis em **/admin/configuracoes**.

## 7. Front-end

- `public/js/loja.js` — vitrine, carrinho, drawer da conta, porta pós-social, checkout (AJAX puro; retry automático em 419).
- `public/js/admin.js` + scripts inline por tela — produtos, pedidos, clientes (senha/campanha respeitam `modo: api|link`), banners.
- `layouts/loja.blade.php` injeta `window.Rotas`, `window.Textos` e `window.ContaEstado`.

## 8. Observações

- `resources/views/welcome.blade.php` é o scaffold padrão do Laravel, **não é renderizado**
  (a `/` é a vitrine) e cita rotas `login`/`register` inexistentes — inofensivo; pode ser
  removido se desejar.
- Auditoria em duas camadas (gatilhos MySQL + trait `Auditoravel`); comandos:
  `auditoria:sincronizar`, `auditoria:ver`, `auditoria:restaurar`.
- Hospedagem InfinityFree: se `CREATE TRIGGER` for bloqueado, a camada da aplicação
  (trait) segue funcionando sozinha.
