# Help â€” Gostosuras (delivery de sobremesas)

Guia de referÃªncia de **todas as funÃ§Ãµes** e **todas as configuraÃ§Ãµes** do sistema.
ReÃºne o que o admin e o cliente podem fazer em cada tela e o significado de cada
configuraÃ§Ã£o, normalmente em **/admin/configuracoes**.

> Stack: PHP 8.3 Â· Laravel 13 Â· MySQL Â· CSS puro Â· AJAX vanilla. Tema salmÃ£o claro.
> Textos da interface vÃªm da tabela `textos` (nÃ£o sÃ£o fixos no cÃ³digo) â€” para mudar um
> texto, altere o valor na tabela, nÃ£o no cÃ³digo.

---

## SumÃ¡rio

1. [Ãrea do cliente (loja)](#1-Ã¡rea-do-cliente-loja)
2. [Ãrea do cliente (conta)](#2-Ã¡rea-do-cliente-conta)
3. [Painel administrativo â€” funÃ§Ãµes por tela](#3-painel-administrativo--funÃ§Ãµes-por-tela)
4. [Todas as configuraÃ§Ãµes](#4-todas-as-configuraÃ§Ãµes)
5. [ConfiguraÃ§Ã£o do cardÃ¡pio digital](#5-configuraÃ§Ã£o-do-cardÃ¡pio-digital)
6. [MÃ³dulo PWA (app / cardÃ¡pio offline)](#6-mÃ³dulo-pwa-app--cardÃ¡pio-offline)
7. [Login social e WhatsApp](#7-login-social-e-whatsapp)
8. [Auditoria e restauraÃ§Ã£o](#8-auditoria-e-restauraÃ§Ã£o)
9. [Comandos Ãºteis](#9-comandos-Ãºteis)
10. [Cupons de desconto](#10-cupons-de-desconto)
11. [Fidelidade (pontos)](#11-fidelidade-pontos)
12. [Multi-lojas (redes de franquia)](#12-multi-lojas-redes-de-franquia)

---

## 1. Ãrea do cliente (loja)

### Vitrine (`/`)
- Lista **categorias** e **produtos ativos** com destaque.
- **CartÃ£o de produto**: imagem/nome/preÃ§o, botÃ£o **Comprar** (ou **Personalizar** quando
  o produto tem complementos). Produto sem estoque mostra "Esgotado"; sem quantidade
  definida mostra "IndisponÃ­vel" (sem botÃ£o).
- Filtro por categoria sem recarregar a pÃ¡gina (AJAX).
- **Vitrine viva**: se o admin mudar preÃ§o/estoque, a pÃ¡gina do cliente aberta detecta
  (polling leve) e atualiza a Ã¡rea de produtos sozinha, avisando com um toast.
- Menu superior traz atalhos: Vitrine, **CardÃ¡pio**, Carrinho e Conta.

### Personalizar complementos
Produtos com complementos exibem **Personalizar**. Ao clicar abre um modal com:
- **Adicionais** (pagam a mais, ex.: + R$ 2,00 por item).
- **RemoÃ§Ãµes** (grÃ¡tis, ex.: sem granulado).
- Seletor de **quantidade** (o total do modal e do carrinho usa a quantidade).
- **Adicionar ao carrinho** envia produto + complementos + quantidade.
- Cada combinaÃ§Ã£o de complementos vira uma **linha separada** no carrinho.

### Carrinho (`/carrinho`)
- Lista as linhas com produto, complementos escolhidos e quantidades.
- **PreÃ§o sempre relido do banco**: se o valor mudou desde a adiÃ§Ã£o, mostra aviso
  "o valor mudou (era X) â€” o pedido usa o valor atual Y".
- Ajusta/remove quantidade e vÃª o subtotal. Sem estoque, a venda Ã© bloqueada com aviso.

### Checkout (`/checkout`)
- **Tipo**: entrega (cobra taxa `taxa_entrega`) ou retirada na loja.
- **EndereÃ§o**: escolhe endereÃ§o salvo ou informa manual.
- **Pagamento**:
  - **Pix** (copia e cola; automÃ¡tico se `efi_ativo`; manual se `chave_pix`).
  - **CartÃ£o online** (Mercado Pago, se `mercadopago_ativo`).
  - **Dinheiro** (com campo de troco).
- Ao finalizar, o pedido Ã© criado em **transaÃ§Ã£o com lock de estoque** (estoque validado
  de novo na hora) e o valor Ã© recalculado do banco â€” nunca paga preÃ§o/estoque antigo.

### ConfirmaÃ§Ã£o (`/pedido/{codigo}`)
- CÃ³digo do pedido, forma de pagamento (Pix com QR/copia-cola quando aplicÃ¡vel) e
  **chave de seguranÃ§a** (o cliente informa no recebimento para validar a entrega).
- Exibe a lista de itens, incluindo os complementos escolhidos.

---

## 2. Ãrea do cliente (conta)

Menu lateral expansÃ­vel (drawer) com:

| SeÃ§Ã£o | FunÃ§Ãµes |
|---|---|
| **Dados pessoais** | Nome, telefone, e-mail; troca da **chave de seguranÃ§a** |
| **EndereÃ§os** | Listar, adicionar, remover, definir principal |
| **CartÃµes** | Salvar (sÃ³ apelido, bandeira e 4 Ãºltimos dÃ­gitos), remover |
| **HistÃ³rico de pedidos** | Consultar pedidos e cÃ³digos |
| **Trocar senha** | AlterÃ§Ã£o da senha |

Fluxos da "porta":
- Login social de conta nova â†’ pede **telefone + chave de seguranÃ§a** para completar.
- Login com a senha temporÃ¡ria `123Mudar` â†’ forÃ§a a troca de senha.
- A senha padrÃ£o redefinida pelo admin tambÃ©m Ã© `123Mudar`.

Login: o cliente entra por `/login` (auth-multi), com e-mail/senha ou login social.

---

## 3. Painel administrativo â€” funÃ§Ãµes por tela

Acesso: `http://localhost:8000/admin` (usuÃ¡rio/senha sÃ³ no banco â€” `php artisan admin:senha`).

| Tela | FunÃ§Ãµes disponÃ­veis |
|---|---|
| **Dashboard** (`/admin`) | Faturamento/pedidos **hoje** e **no mÃªs**, ticket mÃ©dio, grÃ¡fico dos Ãºltimos 14 dias, pedidos recentes, **estoque crÃ­tico** (alerta). |
| **Pedidos** (`/admin/pedidos`) | Lista **filtrÃ¡vel** por status/cliente/cÃ³digo; **detalhe** completo; mudar **status** (novo â†’ em preparo â†’ em entrega â†’ entregue / cancelado â€” cancelar **devolve o estoque**); **gerar nota** (NF pendente, sped-nfe); **encaminhar pedido ao cliente pelo WhatsApp** (API Cloud, ou link `wa.me` se nÃ£o configurado). |
| **Produtos e estoque** (`/admin/produtos`) | CRUD de produtos; **complementos** por produto (adicionais e remoÃ§Ãµes); busca e filtros (baixo/esgotado); ajuste rÃ¡pido de **estoque e estoque mÃ­nimo**; liga/desliga **exibiÃ§Ã£o na vitrine** e **destaque**. |
| **ConfiguraÃ§Ãµes** (`/admin/configuracoes`) | Ver [seÃ§Ã£o 4](#4-todas-as-configuraÃ§Ãµes) + seÃ§Ã£o **CardÃ¡pio digital**. |
| **Item-venda** (`/admin/item-venda`) | Ativa/desativa o mÃ³dulo produto/serviÃ§o (`item_venda_ativo`) e define o tipo vendido (`item_venda_tipo`: produtos / serviÃ§os / ambos). |
| **PWA / App** (`/admin/pwa`) | Gerencia o mÃ³dulo PWA (seÃ§Ã£o [6](#6-mÃ³dulo-pwa-app--cardÃ¡pio-offline)): liga/desliga a instalaÃ§Ã£o e o cardÃ¡pio offline, mostra quantas imagens ficam guardadas, vÃª o link do service worker/manifesto e **renova o cache** dos clientes. |
| **Cupons** (`/admin/cupons`) | CRUD de cupons de desconto (percentual ou fixo), com restriÃ§Ãµes (valor mÃ­nimo, limite de uso e validade), ativaÃ§Ã£o e **promoÃ§Ã£o em destaque** (veja [seÃ§Ã£o 10](#10-cupons-de-desconto)). |
| **Fidelidade** (`/admin/fidelidade`) | Programa de pontos: liga/desliga (`fidelidade_ativo`), define **pontos por R$ 1,00** de compra (`fidelidade_ganho`) e o **valor de cada ponto** no resgate (`fidelidade_ponto_valor`); mostra mÃ©tricas (pontos em circulaÃ§Ã£o, clientes com pontos) â€” veja [seÃ§Ã£o 11](#11-fidelidade-pontos). |
| **Clientes** (`/admin/clientes`) | Contas + mÃ©tricas; **redefinir senha** para `123Mudar` e enviar pelo WhatsApp; **campanha** (oferta) em massa por WhatsApp (API direto ou links prontos). |
| **RelatÃ³rios** (`/admin/relatorios`) | PerÃ­odo personalizÃ¡vel com abas: **vendas por dia**, **produtos mais vendidos**, **previsÃ£o de produÃ§Ã£o por horÃ¡rio** (aplica `margem_producao`), **pagamentos**, **entregas Ã— retiradas**, **estoque crÃ­tico**. Exporta CSV (UTF-8, `;`) e extrato mensal (impressÃ£o/PDF). |
| **Banners** (`/admin/banners`) | CRUD com **agendamento automÃ¡tico** (entra/sai do ar sozinho pelo perÃ­odo). |
| **Auditoria** (`/admin/auditoria`) | HistÃ³rico de tudo criado/alterado/excluÃ­do no banco, filtrÃ¡vel por tabela/aÃ§Ã£o/origem/registro, com detalhe **antigo Ã— novo** e **restauraÃ§Ã£o** por ponto no tempo (exige `MASTER_SENHA`). |

### Fluxo do pedido no admin
1. Cliente finaliza â†’ pedido "novo" cai no **Dashboard** e na lista de **Pedidos**.
2. Admin abre o detalhe, vÃª itens (com complementos), troco, chave de seguranÃ§a e
   telefone.
3. Atualiza o status conforme o andamento.
4. Opcional: clica em "WhatsApp" para **encaminhar o pedido ao cliente**.

---

## 4. Todas as configuraÃ§Ãµes

Editadas em **/admin/configuracoes**. Salvas na tabela `configuracoes` e lidas pelo
helper `config_loja()`. Flags ativam com `1`, desativam com `0`.

### Loja
| Chave | DescriÃ§Ã£o |
|---|---|
| `taxa_entrega` | Valor cobrado na entrega (R$). |
| `chave_pix` | Chave Pix para pagamento manual (copia e cola). |
| `margem_producao` | Margem de seguranÃ§a (%) da previsÃ£o de produÃ§Ã£o por horÃ¡rio. |
| `tema_loja` | Tema visual/identidade ativo: `guloseimas`, `italiana`, `japonesa`, `chinesa` ou `mexicana`. Muda cores (CSS) e nome/slogan/rodapÃ©/herÃ³i da loja e do cardÃ¡pio. Editado na seÃ§Ã£o **Tema da loja** de `/admin/configuracoes`. |

### Nota fiscal (NF-e / NFC-e)
| Chave | DescriÃ§Ã£o |
|---|---|
| `emitir_nfe` | `1` habilita emissÃ£o no sistema (a transmissÃ£o real exige certificado no `.env`: `NFE_CERT_PATH`/`NFE_CERT_SENHA`). |
| `empresa_cnpj` | CNPJ da empresa. |
| `empresa_razao_social` | RazÃ£o social. |
| `empresa_inscricao_estadual` | InscriÃ§Ã£o estadual. |
| `nfe_ambiente` | `2` homologaÃ§Ã£o, `1` produÃ§Ã£o. |

### WhatsApp (Cloud API)
| Chave | DescriÃ§Ã£o |
|---|---|
| `whatsapp_ativo` | `1` = enviar pela API (sem abrir janelas). Sem API, cai em link `wa.me`. |
| `whatsapp_token` | Token permanente do app Meta. |
| `whatsapp_phone_id` | Phone Number ID do nÃºmero de negÃ³cio. |

Usado para: redefinir senha de cliente, campanhas de oferta e **encaminhamento de
pedidos** (tela Pedidos).

### Login social (OAuth puro via auth-multi)
Cada provedor tem `{provedor}_login_ativo`, `{provedor}_client_id` e
`{provedor}_client_secret` (provedores: `google`, `facebook`, `microsoft`, `instagram`).
O controller tambÃ©m grava essas credenciais no `.env` (`AUTH_MULTI_{PROVEDOR}_*`).
No `.env` definem-se tambÃ©m `AUTH_MULTI_MODO` e os `REDIRECT` de cada provedor.

### Pagamento online
| Chave | DescriÃ§Ã£o |
|---|---|
| `mercadopago_ativo` | `1` = botÃ£o "CartÃ£o online" no checkout (Checkout Pro, redireciona pro MP). |
| `mercadopago_access_token` | Access Token do app Mercado Pago (`APP_USR...` ou `TEST-...`). |
| `mercadopago_public_key` | Public Key (reserva p/ checkout transparente). |
| `efi_ativo` | `1` = Pix automÃ¡tico (EfÃ­/Gerencianet). |
| `efi_client_id` / `efi_client_secret` | Credenciais do app EfÃ­. |
| `efi_pix_chave` | Chave Pix cadastrada no app EfÃ­. |
| `efi_sandbox` | `1` = homologaÃ§Ã£o (apisandbox), `0` = produÃ§Ã£o. |

Webhooks (cadastre no painel dos provedores): `/webhooks/mercadopago` e `/webhooks/efi`.

### MÃ³dulo produto/serviÃ§o (item-venda)
| Chave | DescriÃ§Ã£o |
|---|---|
| `item_venda_ativo` | `1` = mÃ³dulo de produtos/serviÃ§os ativo. |
| `item_venda_tipo` | O que o sistema vende: `produtos`, `servicos` ou `ambos`. (Delivery usa `produtos`.) |

Alteradas principalmente na tela `/admin/item-venda`.

### MÃ³dulo PWA (app / cardÃ¡pio offline)
| Chave | DescriÃ§Ã£o |
|---|---|
| `pwa_ativo` | `1` = PWA ativo (service worker registrado â†’ cardÃ¡pio consultÃ¡vel offline + instalÃ¡vel no celular). |
| `pwa_cache_versao` | NÃºmero da versÃ£o do cache. Aumente (ou use "Renovar cache" na tela PWA) para forÃ§ar os clientes a recarregar cardÃ¡pio/preÃ§os/imagens na prÃ³xima abertura. |

Alteradas na tela `/admin/pwa`.

### Fidelidade (pontos)
| Chave | DescriÃ§Ã£o |
|---|---|
| `fidelidade_ativo` | `1` = programa de fidelidade ligado (senÃ£o, o bloco de pontos some do checkout). |
| `fidelidade_ganho` | Pontos ganhos por R$ 1,00 de subtotal do pedido (padrÃ£o `1`; aceita decimal, mÃ­nimo 0,01). |
| `fidelidade_ponto_valor` | Valor em R$ de **cada ponto** no resgate (padrÃ£o `0.10` â†’ 10 pontos = R$ 1,00 de desconto; mÃ­nimo 0,01). |

Alteradas na tela `/admin/fidelidade`.

---

## 5. ConfiguraÃ§Ã£o do cardÃ¡pio digital

Na tela **ConfiguraÃ§Ãµes**, seÃ§Ã£o **"CardÃ¡pio digital"** (fora do formulÃ¡rio):

- **Ver cardÃ¡pio** â€” abre a pÃ¡gina pÃºblica `/cardapio`.
- **URL** â€” link copiÃ¡vel (read-only; clique para selecionar).
- **Copiar link** â€” copia a URL para a Ã¡rea de transferÃªncia.
- **QR code** â€” imagem gerada por `api.qrserver.com` (nenhuma dependÃªncia nova).

O cardÃ¡pio (`/cardapio`) mostra **produtos ativos** por **categorias ativas** (sÃ³
categorias com ao menos um produto ativo). O cliente **pede direto** pelo mesmo fluxo de
carrinho/checkout (botÃµes de compra/personalizar funcionam igual Ã  vitrine). HÃ¡ tambÃ©m um
atalho "CardÃ¡pio" no menu superior da loja.

Para publicar um cardÃ¡pio completo: mantenha as categorias desejadas **ativas** e os
produtos marcados como **exibidos na vitrine** (`ativo`). O cardÃ¡pio herda exatamente isso.

### Temas da loja
A loja tem **5 temas** que mudam as cores (paleta CSS) e a **identidade** (nome, slogan,
rodapÃ©, herÃ³i da vitrine e tÃ­tulo do navegador): **Guloseimas** (confeitaria, padrÃ£o),
**Italiana**, **Japonesa**, **Chinesa** e **Mexicana**. Basta escolher em
**/admin/configuracoes â†’ "Tema da loja"** (`tema_loja`). O tema vale para a loja inteira,
inclusive o cardÃ¡pio. Os textos de cada tema ficam no grupo `tema` da tabela `textos`
(ex.: `tema.italiana.nome`) e as paletas em `public/css/themes/*.css`. O tema padrÃ£o
(Guloseimas) usa as cores base, sem arquivo de override.

---

## 6. MÃ³dulo PWA (app / cardÃ¡pio offline)

O delivery Ã© uma **PWA**: o cliente visita o **cardÃ¡pio** uma vez e depois consegue
**consultÃ¡-lo sem internet** (imagens, CSS e o prÃ³prio HTML ficam guardados no navegador),
alÃ©m de poder **instalar** um atalho na tela inicial do celular (como um app).

- Tela de gestÃ£o: **/admin/pwa** (menu "PWA / App"). LÃ¡ Ã© possÃ­vel:
  - **Ativar/desativar** o PWA (`pwa_ativo`).
  - Ver **quantas imagens** do cardÃ¡pio ficam guardadas para uso offline.
  - Ver os links do **service worker** (`/sw.js`) e do **manifesto** (`/manifest.webmanifest`).
  - **Renovar o cache** dos clientes (incrementa `pwa_cache_versao` â†’ na prÃ³xima abertura
    eles baixam cardÃ¡pio/preÃ§os/imagens atualizados e o cache antigo Ã© descartado).
- ImplementaÃ§Ã£o: JS puro (Service Worker), sem biblioteca externa.
  - `install` prÃ©-guarda CSS/JS do cardÃ¡pio + imagens dos **produtos ativos** e **banners**.
  - NavegaÃ§Ã£o: tenta a rede primeiro; sem internet, cai na Ãºltima cÃ³pia (`network-first`).
  - Assets (css/js/imagens): servidos do cache com atualizaÃ§Ã£o em segundo plano.
  - `POST` (checkout) nÃ£o Ã© interceptado â€” comprar continua exigindo internet.
- A seleÃ§Ã£o de imagens Ã© **dinÃ¢mica**: novos produtos/banners entram no cache sozinhos.
- BotÃ£o **"Instalar app"** aparece no menu da loja nos navegadores que permitem instalar.

---

## 7. Login social e WhatsApp

### Login social
- Configurar em **/admin/configuracoes** os provedores (flag + client id + secret).
- Definir no `.env`: `AUTH_MULTI_MODO` (este projeto: `admin_cliente`) e os `REDIRECT`.
- Cada provedor Ã© ligado/desligado pela chave `{provedor}_login_ativo`.

### WhatsApp (Cloud API)
1. Criar app em developers.facebook.com (Meta).
2. Configurar `whatsapp_ativo`/`whatsapp_token`/`whatsapp_phone_id`.
3. Se nÃ£o configurado, o sistema usa **link `wa.me`** automaticamente (abre o WhatsApp
   preenchido) â€” nada quebra.

---

## 8. Auditoria e restauraÃ§Ã£o

- **Duas camadas**: gatilhos MySQL (gravam toda mudanÃ§a, mesmo via phpMyAdmin) + trait
  `Auditoravel` na aplicaÃ§Ã£o (com autoria: usuÃ¡rio, IP, URL).
- Tabela `logs_auditoria` guarda **antigo Ã— novo** em JSON (nunca campos sensÃ­veis).
- RestauraÃ§Ã£o por **ponto no tempo** (tela ou comando) exige **`MASTER_SENHA`** no `.env`.
  Sem ela, a restauraÃ§Ã£o fica desabilitada.
- Escopo: eventos de negÃ³cio (pedidos, produtos, categorias, clientes, cartÃµes, banners,
  textos, configs, usuÃ¡rios). Carrinho e `pedido_itens` **nÃ£o** sÃ£o auditados.

---

## 9. Comandos Ãºteis

```bash
php artisan admin:senha            # cria/redefine o acesso do painel (email+senha com hash)
php artisan migrate --seed         # monta o banco + textos + configuraÃ§Ãµes + categorias
php artisan auditoria:sincronizar  # (re)gera gatilhos de auditoria (apÃ³s criar tabela nova)
php artisan auditoria:ver          # histÃ³rico (--tabela --acao --registro --limite)
php artisan auditoria:restaurar    # voltar registro ao estado exato de um evento (exige MASTER_SENHA)
php artisan dusk --browse          # teste visual no navegador (obrigatÃ³rio antes de commit)
php artisan view:cache             # compila as views (pega erro de @php( etc.)
```

---

## 10. Cupons de desconto

Cupons deixam o cliente abater um **percentual** ou **valor fixo** no pedido. GestÃ£o
completa em **/admin/cupons**.

### Tipos e restriÃ§Ãµes
- **Tipo**: `percentual` (ex.: 10% de desconto) ou `fixo` (ex.: R$ 5,00 de desconto).
- **Valor mÃ­nimo do pedido** (*carrinho_min*): o cupom sÃ³ vale se o subtotal do pedido
  for maior ou igual a esse valor.
- **Limite de uso** (*limite_uso*): quantas vezes o cupom pode ser usado no total
  (o contador `usos` incrementa a cada pedido concluÃ­do que o usa).
- **Validade** (*valido_ate*): data final de validade (e o campo de dia de inÃ­cio
  *valido_de*). Fora do perÃ­odo, o cupom Ã© recusado.
- **Ativo** (`ativo`): sÃ³ cupons ativos sÃ£o aceitos.

### PromoÃ§Ã£o em destaque
- Ãcone "star" na linha do cupom marca a **promoÃ§Ã£o em destaque** (config
  `cupom_destaque` guarda o cÃ³digo divulgado).
- Um cupom **ativo + vigente** pode ser divulgado na **vitrine** (`/`), no **cardÃ¡pio**
  e como **aviso no checkout** (`promo_destaque()` retorna o cupom em destaque ou `null`).
- BotÃµes **Divulgar**/**Parar divulgaÃ§Ã£o** na tela dos cupons controlam isso.

### AplicaÃ§Ã£o no checkout
- O cliente digita o cÃ³digo no campo **"Cupom de desconto"** do checkout e clica em
  **Aplicar**. A validaÃ§Ã£o Ã© feita por AJAX (`checkout.validar_cupom`) e, se vÃ¡lida, o
  resumo mostra a linha **"Desconto"** (ex.: `- R$ 4,00`).
- O desconto volta a ser **validado no servidor** no fechamento (`store`), de
  N/modo que o total fica: `total = subtotal + taxa - desconto` (nunca negativo).
- Ao confirmar, o pedido guarda `cupom_id`, `cupom_codigo` e `cupom_desconto`; o uso Ã©
  registrado na **confirmaÃ§Ã£o** (incrementa `usos` do cupom) dentro da transaÃ§Ã£o da loja.

### CÃ¡lculo do desconto
- `percentual`: `desconto = round(subtotal * percentual / 100, 2)`, limitado ao subtotal.
- `fixo`: `desconto = min(valor, subtotal)` (nÃ£o deixa o pedido ficar negativo).

Os textos da interface vÃªm do grupo `cupom` da tabela `textos` (e `vitrine.promo`,
`checkout` e `admin_cupons` para as telas correspondentes).

---

## 11. Fidelidade (pontos)

Programa de pontos: o cliente **logado** acumula pontos a cada pedido e pode
**trocar pontos por desconto** no checkout. GestÃ£o em **/admin/fidelidade**.

### Como funciona
- Apenas cliente **logado** participa (o bloco de pontos sÃ³ aparece no checkout autenticado).
- A cada pedido concluÃ­do, o cliente ganha **`fidelidade_ganho` pontos por R$ 1,00 de
  subtotal** (padrÃ£o 1 â†’ R$ 12,80 rende 12 pontos, base arredondada para baixo).
- No resgate, cada ponto vale **`fidelidade_ponto_valor`** (padrÃ£o 0,10 â†’ 10 pontos =
  R$ 1,00 de desconto).
- Saldo da conta do cliente em `clientes.pontos`; o pedido guarda `pontos_ganhos`,
  `pontos_utilizados` e `pontos_desconto`.

### Troca por desconto no checkout
- O cliente informa quantos pontos quer usar no campo **"Seus pontos"** e clica em
  **Usar pontos** (AJAX `checkout.validar_pontos`).
- A validaÃ§Ã£o confere: mÃ³dulo ativo, cliente logado, **saldo suficiente** e que os pontos
  realmente **geram desconto** (o desconto Ã© `min(pontos Ã— valor, subtotal)` â€” nunca passa
  do subtotal).
- O resumo mostra a linha **"Desconto (pontos)"** e o total fica:
  `total = subtotal + taxa âˆ’ desconto_cupom âˆ’ desconto_pontos` (nunca negativo; soma ao
  desconto do cupom).

### No fechamento do pedido (`store`)
- O desconto e o saldo sÃ£o **revalidados de novo no servidor** (mesma transaÃ§Ã£o do
  pedido).
- Os pontos **ganhos sÃ£o creditados** e os **resgatados sÃ£o abatidos** do saldo do cliente
  na mesma transaÃ§Ã£o â€” nunca fica inconsistente.
- Sem mÃ³dulo ativo ou sem cliente logado, qualquer valor de pontos Ã© ignorado.
- A confirmaÃ§Ã£o (`/pedido/{codigo}`) mostra "Desconto (pontos)", **"VocÃª ganhou X pontos
  com este pedido!"** e "VocÃª usou Y pontos neste pedido."

### Tela do admin (`/admin/fidelidade`)
- **Ativar/desativar** o programa (`fidelidade_ativo`).
- **Pontos por R$ 1,00 de compra** (`fidelidade_ganho`).
- **Valor do ponto** em R$ (`fidelidade_ponto_valor`) â€” com exemplo: 0,10 â†’ 10 pontos = R$ 1,00.
- MÃ©tricas no topo: **pontos em circulaÃ§Ã£o** (soma de todos os saldos) e **clientes com pontos**.

Os textos da interface vÃªm do grupo `fidelidade` da tabela `textos` (e `admin_fidelidade`
para a tela de gestÃ£o).
