# COMPONENTES — Partials e componentes Blade reutilizáveis

Inventário de todos os partials/componentes existentes em `resources/views` para
evitar duplicação: **antes de criar uma view nova, procure aqui se a peça já
existe** e reutilize.

> Ref. completa de onde cada um é usado nos fluxos: `SITEMAP.md`.

---

## 1. Partials compartilhados da loja (`resources/views/partials/`)

| Parcial | O que faz | Onde é usado |
|---|---|---|
| `loja_seletor.blade.php` | Seletor de filial (loja) no topo — form POST para `loja.trocar`, mostra option por loja ativa. Renderização condicionada por `mostrar_seletor_loja()` (só aparece com >1 loja ativa e modo não-por-domínio). | `layouts/loja.blade.php` (header) |
| `drawer.blade.php` | **Painel de conta do cliente**: drawer lateral com login/registro/social, dados, senha, endereços, cartões e pedidos — tudo via AJAX para os endpoints `cliente.*`. É toda a interface de conta do app (não há view separada). | `layouts/loja.blade.php` (todas as páginas públicas); botão `#abrir-conta` |
| `modal-personalizar.blade.php` | Modal de personalização de produto com complementos (adicionais/remoções), seletor de quantidade e total dinâmico antes de adicionar ao carrinho. | `layouts/loja.blade.php` (aberto pelo JS quando o produto tem complementos) |
| `promo_destaque.blade.php` | Faixa de promoção em destaque (cupom ativo da config `cupom_destaque`), mostrando desconto % ou em R$. | `vitrine/index.blade.php`, `cardapio/index.blade.php` |
| `complementos_linha.blade.php` | Linha inline (leitura) dos complementos de um item — ex.: "+2 × brigadeiro, −chocolate". | `checkout/index.blade.php`, `pedidos/confirmacao.blade.php`, `admin/pedidos_detalhe.blade.php` |

## 2. Partials da vitrine/cardápio (`resources/views/vitrine/partials/`)

| Parcial | O que faz | Onde é usado |
|---|---|---|
| `resultados.blade.php` | **Bloco de resultados da vitrine**: seções de categorias (chips), destaques e produtos com estado vazio. É servido tanto na página inteira quanto via AJAX (`vitrine.versao`) — mesma fonte de HTML, garantindo consistência (regra 10 da skill). | `vitrine/index.blade.php` + resposta AJAX de `VitrineController@versao` |
| `produto-card.blade.php` | **Cartão de produto**: imagem, categoria, preço, selo destaque/esgotado, aviso de estoque baixo e botão "Adicionar"/"Personalizar" (complementos via data-attributes). Reuso obrigatório para qualquer listagem de produto. | `vitrine/partials/resultados.blade.php`, `cardapio/index.blade.php` |

## 3. Partial do admin (`resources/views/admin/partials/`)

| Parcial | O que faz | Onde é usado |
|---|---|---|
| `complemento_linha.blade.php` | Linha editável de personalização de produto no formulário (tipo adicional/remoção, nome, preço, remover) — clonável via JS. | `admin/produto_form.blade.php` |

## 4. Componentes `<x-config-*>` (`resources/views/components/`)

Usados exclusivamente pela tela `admin/configuracoes.blade.php`:

| Componente | O que faz |
|---|---|
| `<x-config-section>` | Fieldset com legend e descrição para agrupar seções de configuração |
| `<x-config-pair>` | Wrapper de grade de 2 colunas |
| `<x-config-input>` | Input com label reutilizável |
| `<x-config-toggle>` | Checkbox de liga/desliga com label |
| `<x-config-callback>` | Nota exibindo a URI de callback OAuth do provedor social |

## 5. Template de paginação (`resources/views/vendor/pagination/`)

| Arquivo | O que faz | Onde é usado |
|---|---|---|
| `padrao.blade.php` | Paginação customizada do painel (anterior, numérica, próxima). | Usado via `$x->links('vendor.pagination.padrao')` em: `admin/pedidos.blade.php`, `admin/produtos.blade.php`, `admin/relatorios.blade.php` (aba estoque), `admin/clientes.blade.php`, `admin/auditoria.blade.php` |

## 6. Layouts (`resources/views/layouts/`)

| Layout | O que faz | Onde é usado |
|---|---|---|
| `loja.blade.php` | Esqueleto da loja pública (header, seletor de filial, drawer de conta, modal de personalização, footer, injeção `window.Rotas/Textos/ContaEstado`, PWA). | `vitrine`, `cardapio`, `carrinho`, `checkout`, `pedidos/confirmacao` |
| `admin.blade.php` | Esqueleto do painel (sidebar com switcher de loja, menu, rodapé "Ver loja"/sair). | todas as telas de `admin/` |

---

## Regras de ouro ao reutilizar

1. **Antes de criar view nova**, procurar a peça em `partials/`, `vitrine/partials/`,
   `admin/partials/` e `components/` (e conferir `SITEMAP.md`).
2. **Cartão de produto** em qualquer listagem: usar `produto-card.blade.php`
   (nunca recriar o card).
3. **Complementos** em qualquer contexto de leitura: usar `complementos_linha.blade.php`;
   em formulário de produto, a linha editável `complemento_linha.blade.php`.
4. **Lista/grade no admin**: usar classes de `DESIGN_SYSTEM.md` (`.painel-admin`,
   `.tabela`, `.tabela-rolagem`) e os cartões existentes (`.cartao-cupom`,
   `.cartao-banner`, `.cartao-mesa`, `.cartao-metrica`).
5. **Textos de interface**: via `texto('pagina','chave',fallback)` + `TextoSistemaSeeder`
   (nunca hardcoded na view, inclusive em partials).
6. Ao alterar um partial compartilhado, conferir todos os usos listados aqui.