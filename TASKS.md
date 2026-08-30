# TASKS.md — Controle de tarefas do Gostosuras

> **Regra deste arquivo:** manter SEMPRE atualizado. Toda sessão começa lendo este
> arquivo (estado atual) e termina atualizando-o (o que mudou, o que ficou pendente).

---

## Regras de operação definidas pelo usuário

1. **Forma de pagamento é definida NA HORA DE PAGAR, não na hora de fazer o pedido.**
   O pedido não trava a forma de pagamento como decisão final; quem fecha/paga
   (cliente no pagamento do site, garçom/tablet, caixa) escolhe a forma no momento
   do pagamento.
2. Código primeiro → documentação depois → internet.
3. NUNCA excluir/renomear/`TRUNCATE` tabela ou coluna sem permissão explícita.
4. Tudo que aparece na tela vem do banco (nada de dados falsos).
5. Live nascimento: modos **delivery** (vendas online) e **PDV** (mesas, tablet e
   caixa) — cada filial pode operar delivery, PDV ou ambos (`modulos.ativo` 1/0
   por loja ou global, via helper `modulo_ativo`).
6. Lista de Pedidos do admin separa os canais pela ORIGEM: `mesa_id NULL` = online
   (delivery), `mesa_id NOT NULL` = mesa (PDV). `tipo_entrega` é livre dentro de
   cada canal.

---

## Em andamento

- **Adicionar item no modal da mesa (mesas-controle)** — AVALIADO e DESCARTADO pelo
  usuário em 30/08: "entrei agora na forma de fazer pedido e está boa". A tela atual
  (`admin/mesa/{mesa}/pedido`, tablet/garçom) resolve o fluxo; o modal da mesa NÃO
  vai ganhar painel de inclusão. Não implementar.
- **Forma de pagamento na hora de pagar** — regra nº 1 APLICADA ao **tablet/PDV** (30/08):
  o formulário do tablet **não pergunta mais a forma** (dropdown removido) — a pessoa
  não fala como vai pagar enquanto come. O pedido nasce com `forma_pagamento = NULL`
  (migration `2026_09_01_000003_forma_pagamento_nullable_in_pedidos_table`, aprovada
  pelo usuário) e só o **caixa** (`fechar`) define a forma no pagamento.
  - `MesaPedidosController::confirmarPedido` sem o campo/validação; `contaAberta()`
    mantém o pedido cobrável até `pagamento_status = pago`.
  - `forma_pagamento_label(?string)` trata NULL como "A definir" (`pagamentos.forma.indefinido`).
  - Modal mesas-controle omite "Pagamento: " quando null (JS já guardava).
  - Pendente de revisar: **site/checkout** (entrega ao cliente escolhe no pagamento
    — já é o momento do pagamento; manter regra 1 no fluxo online se aplicar).
- **Docs de manutenção** (código ok, documentação depois): criar `docs/MODULOS.md`
  e atualizar `docs/SITEMAP.md`, `AGENTS.md`, `HELP.md`.

## Concluído (resumo recente)

- **Layout do modal do caixa em 2 colunas + pedidos atrativos** (30/08):
  - Modal do caixa agora é `grid minmax(0,1fr) 380px`: pedidos à esquerda
    (`.caixa-pedidos`) + fechamento fixo à direita (`.caixa-fechamento`, sticky);
    em `<1100px` volta a 1 coluna.
  - Item do pedido com **badge de quantidade** (`.modal-mesa__item-qtd`, "2×") em vez
    de prefixo no nome — aproveita o espaço da tela (queixa do usuário em monitor 27").
  - **Pílula de status** em cada pedido (`.modal-mesa__status-pilula--novo/em-preparo/em-entrega`)
    reutilizando as keys `admin_mesas_controle.estado.*`.
  - **Bug "Total: R$ 0,00" corrigido**: `CaixaController::conta()` agora devolve
    `total` por pedido (o total era renderizado de `p.total`, que não existia no JSON).
  - Chaves de texto do caixa renomeadas para casar com o JS: `status_em_preparo`/
    `status_em_entrega` (o JS monta `status_`+status bruto).
- **"Entregue na mesa" — status vira `entregue` automaticamente** (30/08, atualizado):
  - Ao marcar no tablet ou no modal mesas-controle, o pedido **vai direto para
    `entregue`** (não fica mais em `em_entrega`) e grava `entregue_mesa_em`. Endpoint
    `entregueMesa` ficou **idempotente**: pedido já marcado responde 200 e só alinha o
    status (sem erro "Não foi possível marcar").
  - **Confirmação antes de marcar** (pedido do usuário): `window.confirm` no tablet
    (`abertos.confirmar_entrega`) e no modal (`modal.confirmar_entrega`). Dusk aceita
    via `acceptDialog()`.
  - **Scope `Pedido::contaAberta()`** (novo): conta da mesa = `novo`/`em_preparo`/
    `em_entrega` **+ `entregue` ainda não pago**. Aplicado em `MesaPedidosController`
    (pedir/estado/detalhe) e `CaixaController` (estado/conta/pixEfi/fechar) — assim o
    pedido entregue continua na conta da mesa e é cobrável até o caixa registrar
    `pagamento_status=pago` (aí some da conta).
  - **BOTÃO SOME por design quando o pedido já está marcado** (p. tem `entregue_mesa_em`)
    — mostra o selo "Entregue às H:i". O usuário achou que "sumiu o botão" num pedido
    que já havia marcado; comportamento correto.
  - **ROOT CAUSE do "não persiste" resolvida**: `entregue_mesa_em` não tinha `$casts`
    datetime → o Eloquent devolvia **string**, e `optional($str)->format()` retornava
    `null` em silêncio (JSON do detalhe/estado) e quebrava o Blade (`->format()` em string).
    O flag **sempre esteve no banco** — a UI que não o exibia. Fix: `'entregue_mesa_em' => 'datetime'`
    no cast do `Pedido`. Confirmado: `detalhe` agora devolve `09:17`/`09:18` para os
    pedidos reais TB99FCB0/TD8626F3 (Mesa 02).
  - Demais entregas desta feature (mantidas): seção "Pedidos em aberto" no tablet,
    botão em qualquer pedido em aberto, handler dentro do closure do `admin.js`,
    correção do `.replace('ID', p.id)`, pílula + badge + 2 colunas no caixa.
  - Novos textos: `admin_mesa_pedido.abertos.confirmar_entrega`,
    `admin_mesas_controle.modal.confirmar_entrega`, `estado.entregue` (pílula), todos
    seedados.
  - Novos Dusk: `tests/Browser/CaixaTabletEntregaTest.php` (com `acceptDialog()`):
    **3/3** — (1) caixa 2 colunas + pílulas + total por pedido + qtd badge; (2) tablet
    marcando com confirmação, prova no banco: flag + status `entregue`; (3) modal
    mesas-controle marcando pedido `novo` → status `entregue` + flag.
    Screenshots: `caixa_modal_duas_colunas`, `tablet_pedidos_abertos`,
    `tablet_entregue_marcado`, `mesas_controle_modal_entregue_ok`.

- **PDV e Delivery** (módulos por filial, `modulos.ativo`):
  - Renomeado no banco `caixa`→`pdv` e `mesas`→`delivery` (nada apagado, ativo/loja preservados).
  - Menu admin: grupo "PDV" (Caixa, Pedidos das mesas, Mesas QR) + "Vendas" com
    Pedidos (só delivery) gated por `modulo_ativo`.
  - Gates: admin (CaixaController, MesaController, MesaPedidosController, PedidoController)
    e público (Vitrine, Cardápio, Carrinho, Checkout) com página de aviso
    `resources/views/erros/modulo_off.blade.php`.
  - Helpers novos: `canal_atual()`, `modulo_off_view()`, `modulo_off_json()`.
  - Seeders sincronizados e textos (`TextoSistemaSeeder`) persistidos no banco.
  - Dusk `ModulosCaixaTest`: **5/5 passando** (inclui novo teste do gate delivery).

## Testes — estado da suíte

- `php artisan dusk`: **21/27** passando. Falhas listadas são pré-existentes e
  dependem de dados/ambiente (NÃO são das mudanças de PDV/Delivery/layout do caixa):
  - `ExampleTest::test_basic_example` (template Laravel, espera texto "Laravel").
  - `LoginSocialTest::test_botoes_sociais_aparecem_no_drawer` (sem botões sociais na home
    — depende de configuração/estado de login social).
  - `MultilojaTest` (2 falhas — dependem de fixtures "ISO-F..." criadas no setup do teste).
  - `TemasTest::test_tema_padrao_exibe_identidade_guloseimas` (espera loja/tema "Guloseimas").
  - `TrocarLojaTest::test_troca_loja_ativa_pelo_botao_tornar_ativa` (espera loja "teste"/slug "loja-de-teste").
- Browser verdes hoje: `ModulosCaixaTest` **5/5** (52 assertions) e
  `CaixaTabletEntregaTest` **3/3** (32 assertions, com `acceptDialog()` — o `confirm()`
  é aceito DEPOIS do clique) cobrindo: layout 2 colunas do caixa, tablet e modal
  marcando entregue com status `entregue` + flag, e pedido de mesa nascendo com
  `forma_pagamento = NULL`.
- `php artisan test` (feature/unit): **2/2 passando**.
- `view:cache` re-executado sem erros após as mudanças (seeders + views novos).

## Pendências de commit / limpeza

- `entregue_mesa_em` (migration + `entregueMesa`), cast datetime no `Pedido`, status
  vira `entregue` + confirmação, `contaAberta()`, **migration `forma_pagamento` nullable**
  (aprovada) — tudo aguardando validação visual do usuário; Dusk já prova.
- `tests/Browser/CaixaTabletEntregaTest.php` novo (fora de commit) — incluir com entregue_mesa.
- `database/seeders/TesteCardapioSeeder.php` e `debug_homepage.html` untracked — decisão de commit pendente.
- `tests/Browser/ModulosCaixaTest.php` também fora de commit — incluir junto demódulos (ou confirmar com o usuário).
- Limpar scripts de diagnóstico em `C:\Users\Admin\AppData\Local\Temp\opencode\` (check_*, repro_*, dual_read, dump_lojas).