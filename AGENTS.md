# AGENTS.md — Guia para IAs que trabalham neste projeto

Ponto de entrada para qualquer IA (ou desenvolvedor) que for mexer no projeto
**Gostosuras** (delivery de sobremesas com múltiplas lojas/filiais).

> **Leia primeiro:** este arquivo descreve a stack, as convenções e onde estão
> os documentos de contexto. As regras de desenvolvimento obrigatórias do
> usuário vivem na skill `dev-php` (ver "Regras do usuário" abaixo) — toda
> tarefa que envolva código PHP/Laravel/MySQL deve segui-las.

---

## 1. O que é o projeto

Delivery de sobremesas (multi-loja): loja pública (vitrine + cardápio digital +
carrinho + checkout), painel de administração e suporte a **múltiplas lojas
(filiais)** da mesma rede. Cada loja é um registro da tabela `tenants` (pacote
`fabricioagsf/auth-multi`), e os dados de negócio (produtos, pedidos, textos,
configurações) são isolados por `loja_id`.

Clientes podem fazer pedido na mesa direto do celular via QR code.

## 2. Stack (obrigatória)

| Camada | Tecnologia |
|---|---|
| Backend | PHP **8.3** + Laravel **13** (`laravel/framework` ^13.17) |
| Banco | MySQL/MariaDB (compatível com `ONLY_FULL_GROUP_BY`; hospedagem InfinityFree) |
| Front | HTML + **CSS puro** (`public/css`) + **AJAX com JavaScript puro** |
| Tempo real | Polling leve de "versão" (hash) — **não** Livewire, **não** Vue, **não** Filament |
| Autenticação | **`fabricioagsf/auth-multi`** (admin, cliente, login social; multi-tenant por `tenants`) |
| Produtos/serviços | **`fabricioagsf/item-venda`** |
| NF-e / DANFE | `nfephp-org/sped-nfe` + `nfephp-org/sped-da` |
| Testes | PHPUnit (unit/feature) + **Laravel Dusk** para prova visual no navegador |
| Lints | `laravel/pint` (PHP), `node --check` (JS) |

### Motivos das restrições
- Hospedagem InfinityFree só tem **PHP 8.3** → mantém `config.platform.php = 8.3.0`
  fixado no `composer.json` e NUNCA inclui algo que exija PHP > 8.3 (ex. Symony 8.x).
- Bootstrap é **proibido**; tudo é estilizado com CSS puro.
- Nenhuma lib nova entra sem permissão explícita do usuário.

## 3. Regras do usuário (obrigatórias)

As regras completas estão na skill **`dev-php`**. As principais:

- NUNCA excluir/renomear/`TRUNCATE` tabela ou coluna sem permissão explícita.
- NUNCA exibir dados falsos — tudo na tela vem do banco.
- Todos os textos visíveis da interface via helper `texto('pagina','chave','fallback')`
  (tabela `textos`) — proibido hardcoded em view.
- SQL sempre compatível com `ONLY_FULL_GROUP_BY` (sem `SELECT *` + `GROUP BY`).
- Feature só está pronta com prova real executada (HTTP/view/banco **e** teste visual
  no navegador — Dusk `--browse` ou manual com `php artisan serve`).
- Auditoria obrigatória do banco (triggers + trait, tela no admin, restauração com
  senha master `MASTER_SENHA`).
- Em Blade, usar só `@php ... @endphp` em **bloco** (nunca `@php(...)`).
- Ordem de trabalho: **lógica do código → documentação → internet**.

## 4. Convenções de nomenclatura

### Rotas
- Todas as rotas ficam em `routes/web.php`, em grupos com prefixo e nome.
- Grupo público: nomes diretos (`vitrine`, `cardapio`, `carrinho.index`, `checkout`,
  `pedido.confirmacao`, `pwa.manifest`, `pwa.service_worker`, `loja.trocar`).
- Grupo `cliente`: prefixo `/cliente`, namespace `cliente.` (`cliente.login`, ...).
- Grupo `admin`: prefixo `/admin`, **já com `->name('admin.')`** no `Route::group`.
  → **NUNCA** escrever nome de rota começando com `admin.` dentro do grupo (ex.:
  colocar `name('admin.pedidos.index')` gera `admin.admin.pedidos.index`). Usar
  `name('pedidos.index')` etc.
- Nomes no padrão `recurso.acao` (`produtos.index`, `produtos.create`, `produtos.store`,
  `produtos.edit`, `produtos.update`, `produtos.destroy`), com ações de estado
  como verbo (`produtos.ativo`, `banners.ativo`, `cupons.ativo`, `pedidos.status`).
- Depois de editar `routes/web.php`: `php artisan route:clear` (cache de rotas
  costuma ficar obsoleto) e conferir `route:list` contra todo `route('...')` usado.

### Controllers
- Públicos em `app/Http/Controllers/` (ex.: `VitrineController`, `CarrinhoController`).
- Admin em `app/Http/Controllers/Admin/` (`Admin\PedidoController`, ...).
- Métodos com nome de ação HTTP: `index/create/store/show/edit/update/destroy` +
  verbos de estado (`alternarAtivo`, `atualizarStatus`, `trocar`).

### Views
- Pasta por área: `resources/views/vitrine/`, `cardapio/`, `carrinho/`, `checkout/`,
  `pedidos/`, `admin/`, `errors/`. Layouts em `layouts/` (`loja`, `admin`).
- Admin: `arquivo.blade.php` (lista) + `arquivo_form.blade.php` (formulário),
  ex.: `produtos.blade.php` + `produto_form.blade.php`, `lojas.blade.php` +
  `lojas_form.blade.php`.
- Nomes em português e snake_case.

### JS
- `public/js/loja.js` (loja pública) e `public/js/admin.js` (painel) — JS puro,
  sem framework. Assinaturas `window.Rotas`, `window.Textos`, `window.ContaEstado`
  injetadas pelo layout.

### CSS
- `public/css/loja.css` e `public/css/admin.css` + paletas por tema em
  `public/css/themes/*.css`. Padrão **BEM** (`bloco__elemento--modificador`).

### Banco
- Tabelas em português, snake_case, plural (`produtos`, `pedidos`, `pedido_itens`,
  `configuracoes`, `textos`, `logs_auditoria`). Coluna `loja_id` (FK) em toda
  tabela de negócio para isolamento multi-loja.
- Migrations versionadas (`2026_01_01_000001_...`). NUNCA apagar tabela/coluna
  sem permissão.

## 5. Multi-loja (filiais) — como funciona

- Tabela `tenants(id, nome, slug, dominio, status)` — `status` em `ativo|suspenso`.
- Alias `Loja` = `Fabricioagsf\AuthMulti\Models\Tenant` (não existe `App\Models\Loja`).
- Helpers em `app/Support/helpers.php` (autoload via `composer.json`):
  - `loja_atual()` — loja da sessão (`session('loja_id')`) ou a 1ª ativa;
  - `loja_atual_id()`;
  - `lojas_ativas()`;
  - `mostrar_seletor_loja()` — mostra o seletor de filial na vitrine só quando
    há >1 loja ativa **e** `config('auth-multi.tenant_por_dominio')` é `false`.
- Isolamento por `loja_id`: trait `App\Support\PossuiLoja` (grava `loja_id` e
  escopa as queries via `LojaScope`). TODO model de negócio deve usá-la.
- Middleware `GarantirLojaAtiva` fixa a loja na sessão.
- `config/auth-multi.php` → `tenant_por_dominio` (true por padrão): no modo por
  domínio, o **login** resolve o tenant pelo `HTTP_HOST`; o seletor da vitrine
  fica oculto (`mostrar_seletor_loja()`).

## 6. Onde ficam os partials/componentes reutilizáveis

- `resources/views/partials/` — parciais compartilhados da loja pública:
  - `loja_seletor.blade.php` — seletor de filial no topo (condicional);
  - `drawer.blade.php` — **painel de conta do cliente** (drawer lateral, via AJAX);
  - `modal-personalizar.blade.php` — modal de personalização/complementos de produto;
  - `promo_destaque.blade.php` — faixa de promoção em destaque;
  - `complementos_linha.blade.php` — linha de complementos de um item.
- `resources/views/vitrine/partials/` — `resultados.blade.php` (bloco de
  resultados da vitrine, também servido via AJAX) e `produto-card.blade.php`
  (cartão de produto, reutilizado na vitrine E no cardápio).
- `resources/views/admin/partials/` — `complemento_linha.blade.php` (linha
  editável de personalização no formulário de produto).
- `resources/views/components/` — componentes Blade `<x-config-*>` usados só na
  tela `admin/configuracoes.blade.php` (`x-config-section`, `x-config-pair`,
  `x-config-input`, `x-config-toggle`, `x-config-callback`).
- Template de paginação: `resources/views/vendor/pagination/padrao.blade.php`
  (usado como `$x->links('vendor.pagination.padrao')`).

> **Antes de criar uma view nova**, consulte `docs/SITEMAP.md` e
> `docs/COMPONENTES.md` para reaproveitar partials existentes e evitar duplicação.

## 7. Documentos de contexto (pastas `/docs` e raiz)

- **`AGENTS.md`** (este arquivo) — entrada geral.
- **`docs/SITEMAP.md`** — todas as views Blade, rota correspondente, o que faz,
  e partials/componentes usados (organizado por área: admin, loja, login).
- **`docs/DESIGN_SYSTEM.md`** — paleta, fontes, tamanhos, espaçamentos e classes
  CSS reutilizáveis (guia de estilo para telas novas).
- **`docs/COMPONENTES.md`** — inventário dos partials Blade existentes.
- **`docs/HELP.md`** — conteúdo de ajuda do painel (renderizado em `/admin/help`).
- **`docs/DOCUMENTACAO.md`** — documentação técnica geral legada.

## 8. Comandos úteis

```powershell
# Validar tudo depois de mexer em views/rotas
php artisan view:cache        # compila todas as views (pega erro de Blade na hora)
php artisan route:list        # listar rotas
php artisan route:clear       # limpar cache de rotas obsoleto
php -l app/Http/Controllers/Admin/SeuControle.php
node --check public/js/admin.js

# Testes
php artisan test              # PHPUnit unit/feature
php artisan dusk --browse     # teste visual no Chrome (prova final da feature)
```

> Ambiente Windows/PowerShell: o bash tool NÃO suporta `&&` — encadear com `;`
> ou comandos separados. `gh`/git podem não estar no PATH (ver skill `dev-php`).

## 9. Skill associada

- **`dev-php`** — regras de desenvolvimento do usuário (obrigatória sempre).
- **`item-venda`** — regras da lib de produtos/serviços.
- **`manutencao-layout-delivery`** — ativa ao **criar, editar ou revisar telas/views/
  layout** deste projeto: resumo do design system e das convenções, e instruções
  para consultar `SITEMAP.md`/`COMPONENTES.md` antes de criar view nova.