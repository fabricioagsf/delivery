# DESIGN SYSTEM — Gostosuras (delivery de sobremesas)

Guia de estilo extraído do CSS atual (`public/css/loja.css`, `public/css/admin.css`
e temas em `public/css/themes/`). Use-o para manter consistência visual ao criar
ou editar telas.

> Identidade fixa da skill `dev-php`: a cor primária é **salmão claro**, nunca
> verde escuro. Todas as telas novas devem seguir esta paleta.

---

## 1. Paleta de cores (tema padrão "guloseimas")

Cores definidas como variáveis no `:root` de `loja.css` (linhas 6-22). O tema
padrão `themes/guloseimas.css` é idêntico; os demais temas sobrescrevem só as
variáveis de cor (não mudam `--raio`, fontes, `--branco` ou `--erro`).

| Grupo | Variável | Valor (padrão) | Uso |
|---|---|---|---|
| Neutras | `--branco` | `#ffffff` | fundos de cards |
| | `--creme` | `#fff7f2` | fundo da página |
| | `--creme-forte` | `#ffefe5` | superfícies/inputs/bordas |
| Primárias | `--cor-primaria` | `#ffb59c` | **salmão claro** — tema |
| | `--cor-primaria-escura` | `#f79a77` | hover, interruptores, gradientes |
| | `--cor-primaria-texto` | `#743312` | texto sobre salmão |
| | `--cor-link` | `#d96b43` | links/destaques em texto |
| Textos | `--chocolate` | `#4a2c1a` | texto principal / header escuro |
| | `--chocolate-medio` | `#6b4226` | texto secundário (`.texto-suave`) |
| Feedback | `--ok` | `#2e7d32` | sucesso/entregue |
| | `--erro` | `#b3261e` | erro/cancelado |
| | `--alerta` | `#a15c00` | estoque baixo (loja e admin) |
| | `--sucesso-bg` | `#e8f5e9` | fundo ok/entregue/criação |
| | `--sucesso-borda` | `#a5d6a7` | borda de `.alerta--sucesso` |
| | `--erro-bg` | `#ffe9e7` | fundo erro/cancelado/exclusão |
| | `--erro-borda` | `#f3b6b2` | borda de `.alerta--erro` |
| | `--aviso-bg` | `#fff3cd` | fundo aviso (preparo/entrega/alteração) |
| | `--aviso-texto` | `#8a6d00` | texto de aviso |
| | `--novo-bg` | `#ffead6` | fundo do estado `novo` (aguardando) |
| | `--novo-texto` | `#c2501e` | texto do estado `novo` |
| | `--perigo-borda` | `#e0a49b` | borda de ações de perigo |
| Unidade | `--raio` | loja **18px** / admin **14px** | bordas de cards |
| | `--sombra` | loja `0 10px 24px rgba(116,51,18,.12)` / admin `0 8px 20px rgba(116,51,18,.1)` | sombras |
| Tipografia | `--fonte-display` | `Georgia, 'Times New Roman', serif` | títulos/display |
| | `--fonte-corpo` | `system-ui, ... sans-serif` | definida nos dois CSS |

### Cores de apoio (só como variáveis, nunca hardcoded)

| Variável | Valor (padrão) | Uso |
|---|---|---|
| `--input-bg` | `#fffdfa` | fundo de inputs |
| `--esgotado` | `#8d6e63` | etiqueta de produto esgotado |
| `--rotulo-suave` | `#d9a68c` | rótulo suave no topo/sidebar |
| `--claro-sobre-chocolate` | `#ffd9c9` | texto claro sobre `--chocolate` |
| `--rodape-texto` | `#f3d9cb` | texto do rodapé/sidebar escura |
| `--gradiente-claro` | `#ffcbb7` | fim do gradiente do fundo hero |
| `--remocao-texto` | `#8a6a52` | complemento/ingrediente removido |

### Palestas dos temas (`public/css/themes/*.css`)

Cada tema sobrescreve `--cor-primaria`, `--cor-primaria-escura`,
`--cor-primaria-texto`, `--cor-link`, `--chocolate`, `--chocolate-medio`,
`--creme`, `--creme-forte`, `--ok` e `--sombra`.

| Tema | `--cor-primaria` | `--cor-primaria-texto` | Sensação |
|---|---|---|---|
| `guloseimas.css` | `#ffb59c` | `#743312` | padrão (salmão) |
| `italiana.css` | `#e0522f` | `#7a1f0d` | tomate/manjericão |
| `japonesa.css` | `#c23b2e` | `#5c1410` | vermelho-amora/negro |
| `chinesa.css` | `#d62b2b` | `#8b1010` | vermelho-china/dourado |
| `mexicana.css` | `#f4792b` | `#a83210` | laranja/teal |

> Importante: o tema só tem efeito se carregado **depois de** `loja.css` na
> cascata (o layout carrega via `tema_css()`). `--fonte-display` e `--raio` vêm
> do `:root` base e NÃO são sobrescritos pelos temas.

---

## 2. Tipografia

### Famílias
- **Display** (`--fonte-display`): `Georgia, 'Times New Roman', serif` — títulos,
  logotipo, nome de produto, seções, cabeçalhos de categoria.
- **Corpo** (`--fonte-corpo`): `system-ui, -apple-system, 'Segoe UI', Roboto,
  Arial, sans-serif` — todo o resto. Variável definida em `loja.css` e `admin.css`
  (todos os lugares usam `var(--fonte-corpo)`).

### Escala (loja.css)
| Uso | Tamanho |
|---|---|
| hero `h1` | `clamp(2rem, 4.5vw, 3.2rem)` |
| `.titulo-pagina` | 2rem |
| `.logo` | 1.9rem |
| `.secao__titulo` | 1.6rem |
| `.cartao-produto__nome` / `.drawer__topo h2` | 1.25rem / 1.3rem |
| `.cartao-produto__preco` / `.promo-destaque strong` | 1.15rem |
| `.botao--grande` | 1.05rem |
| `.botao` | .95rem |
| corpo / `.alerta` | .9–.95rem |
| `.cartao-produto__descricao` | .88rem |
| etiquetas, `.categoria`, `.status-pilula`, `.logo small` | .72–.75rem (uppercase, letter-spacing .06–.22em) |

### Escala (admin.css)
| Uso | Tamanho |
|---|---|
| `.numero-grande` | 2.4rem |
| `.principal__titulo` | 1.8rem |
| `.cartao-metrica strong` | 1.55rem |
| `.lateral__marca` | 1.4rem |
| `.painel-admin h2` | 1.2rem |
| `.cartao-cupom__info strong` | 1.15rem |
| `.tabela` | .92rem |
| `.texto-suave` | .9rem |
| mini/legendas do menu lateral | .65–.68rem (uppercase) |

---

## 3. Botões

Base: raio **999px** (pílula), borda no estilo do tema.

| Classe | Onde | Padding | Especificidade |
|---|---|---|---|
| `.botao` | loja/admin | 10px 22px (admin 10px 20px) | padrão: bg `--creme-forte`, texto `--cor-primaria-texto` |
| `.botao--chefe` | ambos | igual | gradiente primária→escura; loja ainda tem `border-bottom: 3px solid --cor-primaria-texto` (admin não) |
| `.botao--grande` | loja | 14px 30px | chamativo |
| `.botao--fantasma` | loja | igual | transparente com borda tracejada primária-escura |
| `.botao--whats` | admin | — | verde `#25d366`, branco |
| `.mini-botao` | ambos | 4px 10px (admin 5px 12px), font .75/.78rem | ações em linha |
| `.mini-botao--perigo` | ambos | — | borda `#e0a49b`, texto `--erro` |
| `.mini-botao--salvar` | admin | — | borda primária-escura, bg `--creme-forte` |
| `.chip` | ambos | 9px 20px (admin 7px 16px) | filtros de status; `.chip--ativa` bg primária |
| `.aba` / `.aba.ativa` | loja | 9px | abas |
| `.cardapio-nav__item` | loja | 7px 14px | pílula de categoria |
| `.interruptor` | admin | 44×24 | liga/desliga (`.ligado` → primária-escura) |
| `.botao-social` + `--google/--facebook/--microsoft` | loja | 10px 18px | login social (bordas/marcas) |

### Cards padrão
| Card | Padding | Raio | Acento |
|---|---|---|---|
| `.painel` (loja) | 24px | 18px | sombra |
| `.painel-admin` | 20px 22px | 14px | sombra, mb 22px |
| `.cartao-metrica` | 16px 18px | 14px | `border-top: 5px` primária |
| `.cartao-produto` | 16px 18px | 18px | sombra, hover translateY(-5px) |
| `.resumo` (loja) | 22px | 18px | `border-top: 8px` primária |
| `.linha-carrinho` | 14px 18px | 18px | sombra |
| `.cartao-cupom` / `.cartao-banner` | 14px 16px | 12px | borda `--creme-forte` |
| `.cartao-mesa` | 14px 16px | 14px | borda 2px, sombra |
| `.vazio` | 26px | 18px | borda tracejada primária |

---

## 4. Espaçamentos, larguras e responsividade

### Loja (loja.css)
- **max-width**: `1120px` para `.loja-seletor`, `.topo__conteudo`, `.pagina`, `.rodape__colunas`.
- `.pagina` padding: `28px 20px 60px`. Hero: `64px 48px` → `44px 26px` (<900px).
- **Gaps comuns**: 10px (topo__acoes, chips), 12px (seletor, linha-dupla), 16px (topo), 18px (seção), 22px (grade), 26px (carrinho/checkout/rodapé).
- **Margens de seção**: `.secao` 44px, `.faixa-info` 52px, `.rodape` 60px.
- **Grid**: `.grade` `minmax(240px,1fr)`; `.grade--cardapio` `minmax(210px,1fr)`; carrinho `1fr 320px`; checkout `1fr 340px`; `.linha-dupla` `1fr 1fr`.
- **Breakpoints**: 900px (empilha checkout/carrinho, reduz hero), 560px (grade do cardápio → 150px, nav rolável), 520px (esconde texto de botão, empilha).
- **Inputs**: `padding 11px 13px`, `border 2px --creme-forte`, raio 12px, bg `#fffdfa`; focus `border-color: --cor-primaria-escura`.

### Admin (admin.css)
- **max-width**: `.principal` `1200px`, padding `30px 34px 60px`.
- **Gaps**: 26px (lateral), 22px (`.duas-colunas`, margem-topo), 14px (cartões-resumo, form), 16px (form-admin), 10px (filtros), 6px (gráfico/paginação).
- **Grid**: `.admin-shell` `240px 1fr`; `.duas-colunas` `2fr 1fr` (compacta `1fr 1fr`); `.cartoes-resumo` `minmax(180px,1fr)`; `.controle-mesas__grade` `minmax(200px,1fr)`.
- **Breakpoint**: 900px (dois blocos) e **640px** (desde 08/2026: compacta painel, cartões, filtros e tabelas) — tabelas largas usam `.tabela-rolagem`. Todo o CSS respeita `prefers-reduced-motion`.

---

## 5. Classes reutilizáveis (inventário rápido)

> Detalhes de cada uma (linhas e comportamento) em `public/css/*.css`. Lista por
> categoria para reuso em telas novas.

### Layout / containers
- **loja**: `.pagina`, `.secao`, `.grade`, `.chips`, `.bloco`, `.oculto` (display:none), `.faixa-info`, `.carrinho-layout`, `.lista-carrinho`, `.checkout-layout`, `.checkout-colunas`, `.linha-dupla`, `.abas`, `.rodape*`, `.drawer-velo`, `.drawer`.
- **admin**: `.admin-shell`, `.lateral*`, `.principal`, `.cartoes-resumo`, `.duas-colunas` (`--compacta`), `.margem-topo` (22px !important), `.filtros`, `.filtro-data`, `.filtros--periodo`, `.grafico-barras` (`--rolagem`), `.campanha-grade`, `.config-cardapio`, `.form-inline`, `.rodape-form`, `.escondido`.

### Tabelas (admin)
`.tabela`, `.tabela--estoque` (`.celula-preco`, `.celula-acoes`), `.tabela--clientes` (`.celula-check`), `.tabela-rolagem` (`overflow-x:auto` — usar em tabelas largas), `.linha-esgotada td`.

### Feedback / alertas
`.alerta`, `.alerta--erro`, `.alerta--sucesso`, `.form-mensagem.erro/.ok`, `.campo-cupom__resposta/__erro`, `.retorno-linha`, `.diff-antes` / `.diff-depois`, `.destaque-chave`, `.nota-segura`, `.nota-segura--admin`, `.aviso-estoque-baixo`.

### Badges / status
`.status-pilula` com modificadores em **kebab-case** derivados do status
(mesmo com status do banco em snake_case — helper `status_pilula()` converte):
`--entregue`, `--cancelado`, `--em-entrega`, `--em-preparo`, `--novo`,
`--criacao`, `--alteracao`, `--exclusao`. `.bolha`, `.carrinho-badge`,
`.etiqueta-destaque`, `.etiqueta-esgotado`.

### Formulários
Inputs base (loja `input/select/textarea`; admin `.form-admin input...`), `.caixa-marcar`, `.opcoes-cartao`, `.opcao-cartao__corpo` (estado `:checked`), `.campo-cupom`, `.entrada-estoque`, `.seletor-status`, `.textarea-campanha`, `.linha-complemento`, `.botao-remover-linha`, `.upload-banner`, `.rotulo-mini`, `.entrada-texto`.

### Modais / toast (padrões a seguir para modais novos)
- **loja**: `.modal-personalizar*` (velo com blur, janela central, botão fechar), `@keyframes modal-entra`; `.drawer*`; `.toast` (inferior centralizado, pílula 999px).
- **admin**: `.modal-mesa*` (mesmo padrão velo+janela), `.popup-pedido`, `.toast` (inferior direito, pílula 999px — alinhado à loja).

### Textos utilitários
- **admin**: `.texto-suave` (`--chocolate-medio` .9rem), `.texto-erro`, `.texto-alerta`, `.texto-destaque`, `.texto-normal`, `.texto-maiusculo`.
- **loja**: `.texto-suave` é usado mas NÃO definido no loja.css (herdado/vindo de admin? verificar antes de usar em tela nova).

---

## 6. Padrão de nomenclatura

- **BEM em português**: `bloco__elemento--modificador` (`.cartao-produto__nome`, `.status-pilula--entregue`, `.modal-mesa__acoes-botao--perigo`).
- Estados usam class extra, não pseudo-classe: `.chip--ativa`, `.aba.ativa`, `.interruptor.ligado`, `.toast.visivel`, `.sanfona__item.aberta`.
- Modificadores de status sempre em **kebab-case** (`--em-entrega`, `--em-preparo`),
  mesmo que o valor do banco seja snake (`em_entrega`) — o helper `status_pilula()`
  converte na view/JS; nunca interpolar o status cru na classe.
- Componentes duplicam o BEM quando o mesmo conceito existe na loja e no admin:
  `.loja-seletor__rotulo` (loja) vs `.loja-switcher__rotulo` (admin); `.resumo__linha` (loja) vs `.resumo-linha` (admin).

---

## 7. Inconsistências — status e pendências

**Já corrigidas** (28/08/2026): todas as cores semânticas de feedback viraram
variáveis (`--erro-bg/borda`, `--sucesso-bg/borda`, `--aviso-bg/texto`,
`--perigo-borda`, `--input-bg`, `--esgotado`, `--rotulo-suave`,
`--claro-sobre-chocolate`, `--rodape-texto`, `--gradiente-claro`,
`--remocao-texto`, `--novo-bg/texto`) e os hex repetidos foram substituídos nos
dois CSS; `--fonte-corpo` definida no admin; fallbacks errados corrigidos
(`--cor-primaria-escura` retomava `#743312` → agora `#f79a77`;
`--chocolate-medio` `#7a5a4a` → `#6b4226`; `--sombra` do `.cartao-mesa`
`0 6px 16px .08` → `0 8px 20px .1`); `.chip` do admin ganhou hover e
`.botao--chefe` ganhou a mesma borda inferior da loja; `.alerta` usa
`var(--raio)` e `.toast` virou pílula (999px) no admin; classes de status
normalizadas para kebab (`--em-preparo`/`--em-entrega`, helper
`status_pilula()`); `.lateral__loja__nome` renomeada para `.lateral__loja-nome`
(BEM); `--novo` diferenciado de `--cancelado` (laranja-salmão em vez de vermelho
de erro) e ganhou classe no loja.css; `--alerta`/estoque baixo passou a usar
variável na vitrine; cores de estado das mesas viraram tokens
(`--mesa-livre-borda/bg`, `--mesa-preparo-borda/bg` e `--mesa-modal-preparo-bg`,
`--mesa-entrega-borda/bg` e `--mesa-modal-entrega-bg`, `--mesa-novo-borda/bg`,
`--mesa-modal-novo-bg`, `--mesa-novo-rgb` para os alphas do pulso/popup) e o
título do popup de novo pedido passou a usar `var(--erro)`.

**Pendências para decidir** (não corrigir sem autorização):

1. **`--raio` e `--sombra` divergem** entre loja (18px / 10px) e admin (14px / 8px) — comportamento intencional (admin mais compacto), manter.
2. **`.toast` posição diferente** por área (loja centralizado; admin canto inferior direito) — intencional (UX), mantido.
3. **`.texto-suave` não definido no loja.css** (usado em views da loja mas herdado/sem regra) — verificar antes de usar em tela nova. (O cardápio usa `.cardapio-cabecalho .texto-suave` e conta com o estilo — vale definir no loja.css.)
4. **`.chip--ativa` usa `color: #fff`** (não `--branco`) — trivial, unificado no loja; admin ainda usa `#fff` em `.chip--ativa`, `.botao--chefe` e `.toast.erro`.

**Modernização aplicada (08/2026)**: `.cartao-metrica` com número maior + `tabular-nums` + brilho decorativo + hover; `.principal__titulo` com filete de acento; `.painel-admin h2` com barra lateral; tabelas com zebra e números à direita; sidebar com filete no item ativo; pílulas de status com **dot** (`.status-pilula::before`); campos de filtro e controles com `:focus-visible`; breakpoint **640px** no admin; `prefers-reduced-motion` nos dois CSS; vitrine com imagem de produto em `aspect-ratio: 4/3`, micro-interação de botão (`:active` scale) e sombra em `.botao--chefe`.

Ao criar classe nova, **prefira adicionar à variável/utilitário existente** em vez
de repetir hex no meio da regra.