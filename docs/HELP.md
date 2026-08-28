# Help — Gostosuras (delivery de sobremesas)

Guia de referência de **todas as funções** e **todas as configurações** do sistema.
Reúne o que o admin e o cliente podem fazer em cada tela e o significado de cada
configuração, normalmente em **/admin/configuracoes**.

> Stack: PHP 8.3 · Laravel 13 · MySQL · CSS puro · AJAX vanilla. Tema salmão claro.
> Textos da interface vêm da tabela `textos` (não são fixos no código) — para mudar um
> texto, altere o valor na tabela, não no código.

---

## Sumário

1. [Área do cliente (loja)](#1-área-do-cliente-loja)
2. [Área do cliente (conta)](#2-área-do-cliente-conta)
3. [Painel administrativo — funções por tela](#3-painel-administrativo--funções-por-tela)
4. [Todas as configurações](#4-todas-as-configurações)
5. [Configuração do cardápio digital](#5-configuração-do-cardápio-digital)
6. [Login social e WhatsApp](#6-login-social-e-whatsapp)
7. [Auditoria e restauração](#7-auditoria-e-restauração)
8. [Comandos úteis](#8-comandos-úteis)

---

## 1. Área do cliente (loja)

### Vitrine (`/`)
- Lista **categorias** e **produtos ativos** com destaque.
- **Cartão de produto**: imagem/nome/preço, botão **Comprar** (ou **Personalizar** quando
  o produto tem complementos). Produto sem estoque mostra "Esgotado"; sem quantidade
  definida mostra "Indisponível" (sem botão).
- Filtro por categoria sem recarregar a página (AJAX).
- **Vitrine viva**: se o admin mudar preço/estoque, a página do cliente aberta detecta
  (polling leve) e atualiza a área de produtos sozinha, avisando com um toast.
- Menu superior traz atalhos: Vitrine, **Cardápio**, Carrinho e Conta.

### Personalizar complementos
Produtos com complementos exibem **Personalizar**. Ao clicar abre um modal com:
- **Adicionais** (pagam a mais, ex.: + R$ 2,00 por item).
- **Remoções** (grátis, ex.: sem granulado).
- Seletor de **quantidade** (o total do modal e do carrinho usa a quantidade).
- **Adicionar ao carrinho** envia produto + complementos + quantidade.
- Cada combinação de complementos vira uma **linha separada** no carrinho.

### Carrinho (`/carrinho`)
- Lista as linhas com produto, complementos escolhidos e quantidades.
- **Preço sempre relido do banco**: se o valor mudou desde a adição, mostra aviso
  "o valor mudou (era X) — o pedido usa o valor atual Y".
- Ajusta/remove quantidade e vê o subtotal. Sem estoque, a venda é bloqueada com aviso.

### Checkout (`/checkout`)
- **Tipo**: entrega (cobra taxa `taxa_entrega`) ou retirada na loja.
- **Endereço**: escolhe endereço salvo ou informa manual.
- **Pagamento**:
  - **Pix** (copia e cola; automático se `efi_ativo`; manual se `chave_pix`).
  - **Cartão online** (Mercado Pago, se `mercadopago_ativo`).
  - **Dinheiro** (com campo de troco).
- Ao finalizar, o pedido é criado em **transação com lock de estoque** (estoque validado
  de novo na hora) e o valor é recalculado do banco — nunca paga preço/estoque antigo.

### Confirmação (`/pedido/{codigo}`)
- Código do pedido, forma de pagamento (Pix com QR/copia-cola quando aplicável) e
  **chave de segurança** (o cliente informa no recebimento para validar a entrega).
- Exibe a lista de itens, incluindo os complementos escolhidos.

---

## 2. Área do cliente (conta)

Menu lateral expansível (drawer) com:

| Seção | Funções |
|---|---|
| **Dados pessoais** | Nome, telefone, e-mail; troca da **chave de segurança** |
| **Endereços** | Listar, adicionar, remover, definir principal |
| **Cartões** | Salvar (só apelido, bandeira e 4 últimos dígitos), remover |
| **Histórico de pedidos** | Consultar pedidos e códigos |
| **Trocar senha** | Alterção da senha |

Fluxos da "porta":
- Login social de conta nova → pede **telefone + chave de segurança** para completar.
- Login com a senha temporária `123Mudar` → força a troca de senha.
- A senha padrão redefinida pelo admin também é `123Mudar`.

Login: o cliente entra por `/login` (auth-multi), com e-mail/senha ou login social.

---

## 3. Painel administrativo — funções por tela

Acesso: `http://localhost:8000/admin` (usuário/senha só no banco — `php artisan admin:senha`).

| Tela | Funções disponíveis |
|---|---|
| **Dashboard** (`/admin`) | Faturamento/pedidos **hoje** e **no mês**, ticket médio, gráfico dos últimos 14 dias, pedidos recentes, **estoque crítico** (alerta). |
| **Pedidos** (`/admin/pedidos`) | Lista **filtrável** por status/cliente/código; **detalhe** completo; mudar **status** (novo → em preparo → em entrega → entregue / cancelado — cancelar **devolve o estoque**); **gerar nota** (NF pendente, sped-nfe); **encaminhar pedido ao cliente pelo WhatsApp** (API Cloud, ou link `wa.me` se não configurado). |
| **Produtos e estoque** (`/admin/produtos`) | CRUD de produtos; **complementos** por produto (adicionais e remoções); busca e filtros (baixo/esgotado); ajuste rápido de **estoque e estoque mínimo**; liga/desliga **exibição na vitrine** e **destaque**. |
| **Configurações** (`/admin/configuracoes`) | Ver [seção 4](#4-todas-as-configurações) + seção **Cardápio digital**. |
| **Item-venda** (`/admin/item-venda`) | Ativa/desativa o módulo produto/serviço (`item_venda_ativo`) e define o tipo vendido (`item_venda_tipo`: produtos / serviços / ambos). |
| **Clientes** (`/admin/clientes`) | Contas + métricas; **redefinir senha** para `123Mudar` e enviar pelo WhatsApp; **campanha** (oferta) em massa por WhatsApp (API direto ou links prontos). |
| **Relatórios** (`/admin/relatorios`) | Período personalizável com abas: **vendas por dia**, **produtos mais vendidos**, **previsão de produção por horário** (aplica `margem_producao`), **pagamentos**, **entregas × retiradas**, **estoque crítico**. Exporta CSV (UTF-8, `;`) e extrato mensal (impressão/PDF). |
| **Banners** (`/admin/banners`) | CRUD com **agendamento automático** (entra/sai do ar sozinho pelo período). |
| **Auditoria** (`/admin/auditoria`) | Histórico de tudo criado/alterado/excluído no banco, filtrável por tabela/ação/origem/registro, com detalhe **antigo × novo** e **restauração** por ponto no tempo (exige `MASTER_SENHA`). |

### Fluxo do pedido no admin
1. Cliente finaliza → pedido "novo" cai no **Dashboard** e na lista de **Pedidos**.
2. Admin abre o detalhe, vê itens (com complementos), troco, chave de segurança e
   telefone.
3. Atualiza o status conforme o andamento.
4. Opcional: clica em "WhatsApp" para **encaminhar o pedido ao cliente**.

---

## 4. Todas as configurações

Editadas em **/admin/configuracoes**. Salvas na tabela `configuracoes` e lidas pelo
helper `config_loja()`. Flags ativam com `1`, desativam com `0`.

### Loja
| Chave | Descrição |
|---|---|
| `taxa_entrega` | Valor cobrado na entrega (R$). |
| `chave_pix` | Chave Pix para pagamento manual (copia e cola). |
| `margem_producao` | Margem de segurança (%) da previsão de produção por horário. |

### Nota fiscal (NF-e / NFC-e)
| Chave | Descrição |
|---|---|
| `emitir_nfe` | `1` habilita emissão no sistema (a transmissão real exige certificado no `.env`: `NFE_CERT_PATH`/`NFE_CERT_SENHA`). |
| `empresa_cnpj` | CNPJ da empresa. |
| `empresa_razao_social` | Razão social. |
| `empresa_inscricao_estadual` | Inscrição estadual. |
| `nfe_ambiente` | `2` homologação, `1` produção. |

### WhatsApp (Cloud API)
| Chave | Descrição |
|---|---|
| `whatsapp_ativo` | `1` = enviar pela API (sem abrir janelas). Sem API, cai em link `wa.me`. |
| `whatsapp_token` | Token permanente do app Meta. |
| `whatsapp_phone_id` | Phone Number ID do número de negócio. |

Usado para: redefinir senha de cliente, campanhas de oferta e **encaminhamento de
pedidos** (tela Pedidos).

### Login social (OAuth puro via auth-multi)
Cada provedor tem `{provedor}_login_ativo`, `{provedor}_client_id` e
`{provedor}_client_secret` (provedores: `google`, `facebook`, `microsoft`, `instagram`).
O controller também grava essas credenciais no `.env` (`AUTH_MULTI_{PROVEDOR}_*`).
No `.env` definem-se também `AUTH_MULTI_MODO` e os `REDIRECT` de cada provedor.

### Pagamento online
| Chave | Descrição |
|---|---|
| `mercadopago_ativo` | `1` = botão "Cartão online" no checkout (Checkout Pro, redireciona pro MP). |
| `mercadopago_access_token` | Access Token do app Mercado Pago (`APP_USR...` ou `TEST-...`). |
| `mercadopago_public_key` | Public Key (reserva p/ checkout transparente). |
| `efi_ativo` | `1` = Pix automático (Efí/Gerencianet). |
| `efi_client_id` / `efi_client_secret` | Credenciais do app Efí. |
| `efi_pix_chave` | Chave Pix cadastrada no app Efí. |
| `efi_sandbox` | `1` = homologação (apisandbox), `0` = produção. |

Webhooks (cadastre no painel dos provedores): `/webhooks/mercadopago` e `/webhooks/efi`.

### Módulo produto/serviço (item-venda)
| Chave | Descrição |
|---|---|
| `item_venda_ativo` | `1` = módulo de produtos/serviços ativo. |
| `item_venda_tipo` | O que o sistema vende: `produtos`, `servicos` ou `ambos`. (Delivery usa `produtos`.) |

Alteradas principalmente na tela `/admin/item-venda`.

---

## 5. Configuração do cardápio digital

Na tela **Configurações**, seção **"Cardápio digital"** (fora do formulário):

- **Ver cardápio** — abre a página pública `/cardapio`.
- **URL** — link copiável (read-only; clique para selecionar).
- **Copiar link** — copia a URL para a área de transferência.
- **QR code** — imagem gerada por `api.qrserver.com` (nenhuma dependência nova).

O cardápio (`/cardapio`) mostra **produtos ativos** por **categorias ativas** (só
categorias com ao menos um produto ativo). O cliente **pede direto** pelo mesmo fluxo de
carrinho/checkout (botões de compra/personalizar funcionam igual à vitrine). Há também um
atalho "Cardápio" no menu superior da loja.

Para publicar um cardápio completo: mantenha as categorias desejadas **ativas** e os
produtos marcados como **exibidos na vitrine** (`ativo`). O cardápio herda exatamente isso.

---

## 6. Login social e WhatsApp

### Login social
- Configurar em **/admin/configuracoes** os provedores (flag + client id + secret).
- Definir no `.env`: `AUTH_MULTI_MODO` (este projeto: `admin_cliente`) e os `REDIRECT`.
- Cada provedor é ligado/desligado pela chave `{provedor}_login_ativo`.

### WhatsApp (Cloud API)
1. Criar app em developers.facebook.com (Meta).
2. Configurar `whatsapp_ativo`/`whatsapp_token`/`whatsapp_phone_id`.
3. Se não configurado, o sistema usa **link `wa.me`** automaticamente (abre o WhatsApp
   preenchido) — nada quebra.

---

## 7. Auditoria e restauração

- **Duas camadas**: gatilhos MySQL (gravam toda mudança, mesmo via phpMyAdmin) + trait
  `Auditoravel` na aplicação (com autoria: usuário, IP, URL).
- Tabela `logs_auditoria` guarda **antigo × novo** em JSON (nunca campos sensíveis).
- Restauração por **ponto no tempo** (tela ou comando) exige **`MASTER_SENHA`** no `.env`.
  Sem ela, a restauração fica desabilitada.
- Escopo: eventos de negócio (pedidos, produtos, categorias, clientes, cartões, banners,
  textos, configs, usuários). Carrinho e `pedido_itens` **não** são auditados.

---

## 8. Comandos úteis

```bash
php artisan admin:senha            # cria/redefine o acesso do painel (email+senha com hash)
php artisan migrate --seed         # monta o banco + textos + configurações + categorias
php artisan auditoria:sincronizar  # (re)gera gatilhos de auditoria (após criar tabela nova)
php artisan auditoria:ver          # histórico (--tabela --acao --registro --limite)
php artisan auditoria:restaurar    # voltar registro ao estado exato de um evento (exige MASTER_SENHA)
php artisan dusk --browse          # teste visual no navegador (obrigatório antes de commit)
php artisan view:cache             # compila as views (pega erro de @php( etc.)
```
