# SITEMAP — Gostosuras (delivery de sobremesas)

Mapa de todas as views Blade do projeto, com a rota correspondente, o que a
tela faz e os partials/componentes reutilizados. Organizado por área.

> **Regra:** antes de criar uma view nova, consulte este arquivo e o
> `COMPONENTES.md` para reaproveitar partials existentes em vez de duplicar.

---

## 1. Loja pública (vitrine / cardápio / carrinho / checkout / pedido)

| Arquivo | Rota | O que faz | Partials/componentes |
|---|---|---|---|
| `layouts/loja.blade.php` | esqueleto de `vitrine`, `cardapio`, `carrinho.index`, `checkout`, `pedido.confirmacao` | Header (logo, seletor de filial, cardápio, instalar app, conta, carrinho), footer, injeção de `window.Rotas`/`window.Textos`/`window.ContaEstado`, service worker PWA e botão "instalar app" | `partials.loja_seletor` (condicional via `mostrar_seletor_loja()`), `partials.drawer`, `partials.modal-personalizar` |
| `vitrine/index.blade.php` | `vitrine` = `GET /` (com `?categoria=`) | Home: carrossel de banners (ou hero), faixa de promo destaque, chips de categoria, grade de destaques, grade de produtos e faixa de informações (pagamento/entrega/retirada) | `@extends('layouts.loja')`; `partials.promo_destaque`; `vitrine.partials.resultados` |
| `vitrine/partials/resultados.blade.php` | AJAX `vitrine.versao` (`GET /vitrine/versao`) + incluída na home | Bloco de resultados da vitrine (categorias, destaques, produtos, estado vazio) — mesma fonte de HTML para renderização completa e AJAX | `vitrine.partials.produto-card` |
| `vitrine/partials/produto-card.blade.php` | parcial | Cartão de produto (imagem, categoria, preço, selo destaque/esgotado, aviso de estoque, botão Adicionar/Personalizar) — reusado na vitrine **e** no cardápio | — |
| `cardapio/index.blade.php` | `cardapio` = `GET /cardapio` (com `?mesa=` QR) | Cardápio digital: cabeçalho com contador, nav de categorias por âncora, grade de produtos por categoria | `@extends('layouts.loja')`; `partials.promo_destaque`; `vitrine.partials.produto-card` |
| `carrinho/index.blade.php` | `carrinho.index` = `GET /carrinho` | Carrinho: itens com complementos, contadores (+/-), avisos de preço/estoque alterado, resumo com subtotal e "Finalizar pedido" | `@extends('layouts.loja')` |
| `checkout/index.blade.php` | `checkout` = `GET /checkout` | Checkout: dados, entrega×retirada (endereços salvos), formas de pagamento (Pix, Pix Efí, Mercado Pago, cartão, dinheiro/troco), cupom, pontos, observações e resumo dinâmico | `@extends('layouts.loja')`; `partials.complementos_linha` |
| `pedidos/confirmacao.blade.php` | `pedido.confirmacao` = `GET /pedido/{codigo}` | Confirmação pós-pedido: código, chave de segurança, resumo com descontos, instruções por forma de pagamento e dados de entrega/retirada | `@extends('layouts.loja')`; `partials.complementos_linha` |

### Parciais compartilhados da loja (`resources/views/partials/`)

| Arquivo | Usada em | O que faz |
|---|---|---|
| `loja_seletor.blade.php` | `layouts/loja` (condicional) | Seletor de filial no topo (form POST para `loja.trocar`) |
| `drawer.blade.php` | `layouts/loja` (todas as páginas) | Painel de conta do cliente (drawer lateral, dados via AJAX de `cliente.painel`) |
| `modal-personalizar.blade.php` | `layouts/loja` | Modal de personalização de produto com complementos |
| `promo_destaque.blade.php` | `vitrine/index`, `cardapio/index` | Faixa de promoção em destaque (cupom) |
| `complementos_linha.blade.php` | checkout, pedido, admin/pedidos_detalhe | Linha inline de complementos de um item |

### Service worker / manifesto

| Arquivo | Rota | O que faz |
|---|---|---|
| `pwa/sw.blade.php` | `pwa.service_worker` = `GET /sw.js` | Service worker PWA (cache de assets, página offline, versão de cache do painel) |
| (JSON) | `pwa.manifest` = `GET /manifest.webmanifest` | Manifesto PWA (não é view) |

---

## 2. Painel do cliente (conta) — via AJAX, sem view própria

O painel do cliente **não tem pagina Blade**: é o partial
`partials/drawer.blade.php` + endpoints JSON do `ContaController`
(`cliente.painel`, `cliente.dados`, `cliente.senha`, `cliente.completar`,
`cliente.enderecos.*`, `cliente.cartoes.*`). Login/registro também via JSON
(`cliente.login`, `cliente.registrar`). Tela alternativa provida pelo pacote
`auth-multi`: `GET /login` (`portal.blade.php` do vendor).

---

## 3. Painel de administração (`resources/views/admin/`)

| Arquivo | Rota | O que faz | Partials/componentes |
|---|---|---|---|
| `layouts/admin.blade.php` | esqueleto de todas as telas admin | Sidebar com seletor de loja ativa, menu completo, "Ver loja" e sair | `@yield('conteudo'/'titulo'/'titulo_pagina')`, `@stack('scripts')` |
| `dashboard.blade.php` | `admin.dashboard` = `GET /admin/painel` | Visão geral: faturamento/pedidos/ticket (hoje/mês), gráfico 14 dias, pedidos por status, recentes e estoque crítico | `@extends('layouts.admin')` |
| `pedidos.blade.php` | `admin.pedidos.index` = `GET /admin/pedidos` | Lista de pedidos: busca, filtro por status (chips) e troca rápida de status | `@extends('layouts.admin')`; paginação `padrao` |
| `pedidos_detalhe.blade.php` | `admin.pedidos.show` = `GET /admin/pedidos/{pedido}` | Detalhe: itens/complementos, troco, notas fiscais, status, WhatsApp, dados do cliente | `@extends('layouts.admin')`; `partials.complementos_linha` |
| `produtos.blade.php` | `admin.produtos.index` = `GET /admin/produtos` | Gestão de produtos/estoque: busca, filtros, edição inline de estoque/mínimo, interruptores vitrine/destaque, remover | `@extends('layouts.admin')`; paginação `padrao` |
| `produto_form.blade.php` | `admin.produtos.create` / `admin.produtos.edit` | Form de produto: identificação, imagem, preço/estoque por loja, personalizações, vitrine/destaque | `@extends('layouts.admin')`; `admin.partials.complemento_linha` |
| `relatorios.blade.php` | `admin.relatorios` = `GET /admin/relatorios` | Relatórios (período de/até) com abas: vendas, produtos, horários, pagamentos, entregas, estoque; cartões-resumo e gráficos | `@extends('layouts.admin')`; paginação `padrao` (aba estoque) |
| `relatorio_simples.blade.php` | `admin.relatorios.simples` (imprime/PDF/CSV) | Relatório simples standalone (HTML puro para impressão, não usa layout admin) | — |
| `relatorio_mensal.blade.php` | `admin.relatorios.mensal` (PDF) | Extrato mensal: navegação de mês, resumo, vendas dia a dia com acumulado, produtos vendidos; CSS de impressão | `@extends('layouts.admin')`; `@push('scripts')` |
| `clientes.blade.php` | `admin.clientes.index` = `GET /admin/clientes` | Clientes: métricas, campanha de oferta por WhatsApp e lista com total gasto | `@extends('layouts.admin')`; paginação `padrao` |
| `banners.blade.php` | `admin.banners.index` | Lista de banners: cartões, agendamento, interruptor, editar/remover | `@extends('layouts.admin')` |
| `banners_form.blade.php` | `admin.banners.create` / `.edit` | Form de banner: upload/preview, link, agendamento, ativo | `@extends('layouts.admin')` |
| `cupons.blade.php` | `admin.cupons.index` | Cupons: cartões com desconto, usos, validade, destacar na vitrine, ligar/desligar, editar/remover | `@extends('layouts.admin')` |
| `cupons_form.blade.php` | `admin.cupons.create` / `.edit` | Form de cupom: tipo %/fixo, valor, mínimo, usos, validade, ativo | `@extends('layouts.admin')` |
| `configuracoes.blade.php` | `admin.configuracoes.index` | Configurações: QR code de mesa, taxa de entrega, margem, NF-e, WhatsApp API, login social, pagamento, item-venda, tema | `@extends('layouts.admin')`; `<x-config-*>` (todos os 5) |
| `fidelidade.blade.php` | `admin.fidelidade.index` | Programa de pontos: ativar, pontos por R$, valor do ponto, métricas | `@extends('layouts.admin')` |
| `help.blade.php` | `admin.help` = `GET /admin/help` | Ajuda do painel (renderiza `docs/HELP.md` via `{!! $html !!}`) | `@extends('layouts.admin')` |
| `item_venda.blade.php` | `admin.item-venda.index` | Módulo produtos/serviços: ativar, tipo vendido, resumo | `@extends('layouts.admin')` |
| `lojas.blade.php` | `admin.lojas.index` | Lojas da rede: cards com slug/domínio, totais, tornar ativa, editar, suspender | `@extends('layouts.admin')` |
| `lojas_form.blade.php` | `admin.lojas.create` / `.edit` | Form de loja: nome, slug, domínio, status | `@extends('layouts.admin')` |
| `mesas.blade.php` | `admin.mesas.index` | Mesas: cards com nome/código/capacidade, link QR, editar, ativar | `@extends('layouts.admin')` |
| `mesas_form.blade.php` | `admin.mesas.create` / `.edit` | Form de mesa: nome, código, capacidade, ativa | `@extends('layouts.admin')` |
| `mesas_pedidos.blade.php` | `admin.mesas-controle.index` | Painel tempo real de pedidos da mesa: grade de cartões, modal de detalhe, popup + som | `@extends('layouts.admin')` |
| `auditoria.blade.php` | `admin.auditoria.index` | Auditoria do banco: filtros por registro/tabela/ação/origem, tabela imutável | `@extends('layouts.admin')`; paginação `padrao` |
| `auditoria_detalhe.blade.php` | `admin.auditoria.show` | Detalhe do evento: diff Antes/Depois, snapshot, restauração com senha master | `@extends('layouts.admin')` |
| `pwa.blade.php` | `admin.pwa.index` | Config PWA: ativar, métricas imagem/cache, renovar cache, links | `@extends('layouts.admin')` |

### Parcial do admin

| Arquivo | Usada em | O que faz |
|---|---|---|
| `admin/partials/complemento_linha.blade.php` | `admin/produto_form.blade.php` | Linha editável de personalização (tipo adicional/remoção, nome, preço), clonável via JS |

### Componentes `<x-config-*>` (só em `configuracoes.blade.php`)

| Arquivo | Uso | O que faz |
|---|---|---|
| `components/config-section.blade.php` | `<x-config-section>` | Fieldset com legend e descrição |
| `components/config-pair.blade.php` | `<x-config-pair>` | Grade de 2 colunas |
| `components/config-input.blade.php` | `<x-config-input>` | Input com label |
| `components/config-toggle.blade.php` | `<x-config-toggle>` | Checkbox liga/desliga |
| `components/config-callback.blade.php` | `<x-config-callback>` | Nota com URI de callback OAuth |

---

## 4. Erros e paginação

| Arquivo | Rota | O que faz |
|---|---|---|
| `errors/base.blade.php` | genérica (layout dos erros) | Cartão de erro com código, título, mensagem e "Voltar para a loja" |
| `errors/403.blade.php` | HTTP 403 | "Essa área não é sua (ainda!)" → `@extends('errors.base')` |
| `errors/404.blade.php` | HTTP 404 | "Essa página derreteu..." → `@extends('errors.base')` |
| `errors/419.blade.php` | HTTP 419 (CSRF/sessão) | "Seu doce esfriou / sessão expirada" → `@extends('errors.base')` |
| `errors/500.blade.php` | HTTP 500 | "Caiu açúcar no servidor" → `@extends('errors.base')` |
| `errors/503.blade.php` | HTTP 503 (manutenção) | "Estamos reabastecendo a vitrine" → `@extends('errors.base')` |
| `vendor/pagination/padrao.blade.php` | usado via `$x->links('vendor.pagination.padrao')` | Paginação custom do admin (não usa template Tailwind) |

---

## 5. Perfil do entregador

**Não existe.** Não há painel/views de entregador neste projeto (a busca
por "entregador/delivery" só retorna menções ao nome do negócio). Se surgir,
criar área nova e atualizar este arquivo + menu do `layouts/admin.blade.php`.

---

## 6. Login (fora deste projeto — pacote `fabricioagsf/auth-multi`)

| Arquivo (vendor) | Rota | O que faz |
|---|---|---|
| `auth-multi::admin` (`vendor/.../resources/views/admin.blade.php`) | `authmulti.admin.tela` = `GET /admin` | **Login do admin** (e-mail/senha/manter conectado); alvo do redirect de `auth.multi:admin` |
| `auth-multi::portal` (`.../portal.blade.php`) | `authmulti.portal.tela` = `GET /login` | Login do portal (cliente + prestador) e botões sociais |
| `auth-multi::base` (`.../base.blade.php`) | esqueleto das duas acima | Layout de autenticação do pacote (cartão, alertas, CSS/JS próprios) |