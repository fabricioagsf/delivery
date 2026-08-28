# Gostosuras — Loja de sobremesas com delivery e painel administrativo

Loja online de doces artesanais (brigadeiros, chocolates, gostosuras) com:

- **Vitrine** com categorias, destaques e carrinho via AJAX (JavaScript puro, sem frameworks)
- **Complementos personalizáveis** (adição paga ou remoção grátis) por produto, escolhidos
  na hora do pedido
- **Cardápio digital público** (`/cardapio`) com QR code, onde o cliente pede direto
- **Checkout** com Pix, cartão ou dinheiro; entrega em casa ou retirada na loja
- **Conta do cliente** num menu lateral expansível: dados pessoais, endereços, cartões,
  histórico de pedidos e **chave de segurança** (informada pelo cliente no recebimento
  para validar a entrega)
- **Painel administrativo** (`/admin`) com dashboard, pedidos, estoque, relatórios e
  encaminhamento de pedidos ao cliente pelo WhatsApp

> **Documentação:** [Help — funções e configurações](docs/HELP.md) ·
> [Documentação técnica (rotas/services/chaves)](docs/DOCUMENTACAO.md)

## Stack

PHP 8.3 · Laravel 13 · MySQL · HTML · CSS puro (sem Bootstrap/Tailwind) · AJAX vanilla.
Todas as bibliotecas ficam fixadas em `composer.json` com `"platform": {"php": "8.3.0"}`
para compatibilidade com a hospedagem InfinityFree.

Bibliotecas do usuário integradas:

- **`fabricioagsf/auth-multi`** — autenticação unificada (admin e cliente) com login
  social nativo (Google / Facebook / Microsoft / Instagram) e multi-tenant por domínio
  (tabela `tenants`). Protege as áreas pelos middlewares `auth.multi:admin` e
  `auth.multi:cliente`.
- **`fabricioagsf/item-venda`** — módulo de produtos/serviços (superset da tabela
  `produtos`). Controlado pela flag `item_venda_ativo` com tela própria em
  `/admin/item-venda`.
- **`nfephp-org/sped-nfe`** + **`nfephp-org/sped-da`** — emissão de NF-e e PDF/DANFE
  (prontas para uso; veja "NF-e / DANFE").
- **`laravel/dusk`** (dev) — teste visual no navegador antes de cada commit.

## Como rodar

```bash
composer install
cp .env.example .env        # configure o MySQL em DB_* 
php artisan key:generate
php artisan migrate --seed
php artisan serve           # http://localhost:8000
```

### Criar o admin

Usuário e senha do painel vivem **somente no banco** (tabela `users`) — nunca em
código ou `.env`. Para cadastrar ou redefinir o acesso (pergunta e-mail e senha,
confere, e grava com hash):

```bash
php artisan admin:senha
```

Acesse `http://localhost:8000/admin`. Seeders nunca sobrescrevem o admin:
`AdminSeeder` apenas avisa se ainda não existe acesso no banco.

## Regras de venda e estoque

- **Um produto só é vendido com quantidade maior que zero.** Sem quantidade
  definida (`estoque = NULL`) o produto fica **indisponível** na vitrine
  (mostra "Indisponível", sem botão de comprar).
- `estoque = 0` → aparece como **"Esgotado"**, também sem botão de comprar.
- A quantidade é revalidada no carrinho e de novo no checkout com
  `lockForUpdate` dentro da transação do pedido; sem quantidade a venda é
  bloqueada e o cliente é avisado.
- Cancelar um pedido no admin devolve os itens ao estoque automaticamente.
- `estoque_minimo` alimenta os alertas de "estoque baixo" no dashboard e nos relatórios.
- **Serviço/serviços:** este projeto não possui tabela de serviços; o equivalente
  (só vende o que está **ativo**) já é aplicado a produtos em vitrine, carrinho e
  checkout (`ativo = true` obrigatório em todas as etapas).

## Complementos (personalizações por produto)

Cada produto pode ter complementos de dois tipos, configurados no editor de produto:

- **Adicional**: permite ao cliente adicionar algo por um preço a mais (ex.: + brigadeiro R$ 2,00).
- **Remoção**: opção grátis de retirar ingrediente (ex.: sem granulado).

No carrinho, cada combinação de complementos gera uma **linha separada** (chave md5) para
faturamento e estoque corretos. O preço dos complementos é sempre **recomputado do banco**
na renderização do carrinho e do checkout (regra de ouro — nunca preço congelado). O pedido
guarda o snapshot dos complementos escolhidos, exibido na confirmação e no painel admin.

## Cardápio digital

Rota pública `GET /cardapio` (`CardapioController`): reúne os **produtos ativos** agrupados
pelas **categorias ativas** num layout de menu (`resources/views/cardapio/index.blade.php`),
com navegação âncora por categoria. O cliente **pede direto do cardápio** usando o mesmo
fluxo de carrinho/checkout da loja (reaproveita `vitrine.partials.produto-card` e `loja.js`).

O link e o **QR code** do cardápio aparecem na tela **Configurações** do admin (seção
"Cardápio digital", fora do formulário): botão "Ver cardápio", campo de URL copiável e QR
gerado por serviço externo `api.qrserver.com` (nenhuma dependência nova).

- Link "Cardápio" também está no menu superior da loja.
- Só aparecem categorias que tenham ao menos um produto ativo.

## Temas (identidade cultural)

A loja tem **5 temas** que trocam a **identidade cultural** (cores + nome + slogan +
rodapé + herói + título): **Guloseimas** (confeitaria, padrão), **Italiana**, **Japonesa**,
**Chinesa** e **Mexicana**. Seleção em **/admin/configuracoes → "Tema da loja"** (`tema_loja`).
As paletas ficam em `public/css/themes/*.css` e a identidade de cada tema no grupo `tema`
da tabela `textos`. O **conteúdo do cardápio** é gerido pelo cadastro de produtos (a
regionalidade de conteúdo será tratada pelas filiais/multi-lojas — veja o roadmap).

## Painel de administração

| Página | Conteúdo |
| --- | --- |
| Dashboard | Faturamento/pedidos hoje e no mês, ticket médio, gráfico dos últimos 14 dias, pedidos recentes, estoque crítico |
| Pedidos | Lista filtrável por status/cliente/código, alteração de status (novo → em preparo → em entrega → entregue / cancelado), detalhe completo com troco, lembrete da chave de segurança e **encaminhamento ao cliente pelo WhatsApp** (API ou link `wa.me`) |
| Produtos e estoque | Busca, filtros (baixo/esgotado), ajuste rápido de estoque e mínimo, liga/desliga vitrine e destaque, **complementos do produto** |
| Configurações | Loja (taxa de entrega, chave Pix, margem de produção, **tema da loja**), NF-e, WhatsApp Cloud API, login social, pagamento online, módulo item-venda e **Cardápio digital (link + QR)** |
| Clientes | Contas, métricas e redefinição de senha (envio via WhatsApp) |
| Relatórios | Período personalizável com abas: vendas por dia, produtos mais vendidos, **previsão de produção por horário**, pagamentos, entregas × retiradas, estoque crítico |
| Banners | CRUD com agendamento automático (entra/sai do ar sozinho) |
| Auditoria | Histórico de tudo criado/alterado/excluído no banco + restauração por ponto no tempo (exige senha master) |
| PWA / App | Módulo PWA: ativa/desativa o cardápio offline e a instalação, mostra nº de imagens guardadas, links do service worker/manifesto e **renova o cache** dos clientes |

### PWA (app / cardápio offline)

O delivery é uma **PWA** (JS puro, sem biblioteca externa). O cliente visita o
**cardápio** (`/cardapio`) uma vez e depois o **consulta sem internet** (Service Worker
guarda HTML, CSS/JS e as imagens dos produtos/banners ativos), além de poder **instalar** um
atalho na tela inicial do celular. Gestão em `/admin/pwa` (menu "PWA / App"). Veja
[docs/HELP.md](docs/HELP.md#6-módulo-pwa-app--cardápio-offline) para detalhes.

### Métrica de produção por horário

Relatório *Horários*: soma os itens vendidos em cada hora do dia no período escolhido,
divide pelos dias que tiveram venda (média diária) e aplica margem de segurança da
configuração `margem_producao` (%). Resultado: quantos itens produzir em cada faixa
de horário. Ajuste a margem em `configuracoes.margem_producao`.

Todas as queries agregadas são compatíveis com MySQL/MariaDB em modo `ONLY_FULL_GROUP_BY`.

## Textos da interface

Nenhum texto é fixo no código: tudo vem da tabela `textos` (`pagina`, `chave`, `valor`)
via helper global `texto('pagina', 'chave', 'fallback')`, semeada pelo
`TextoSistemaSeeder`. Para mudar um texto: `UPDATE textos SET valor = ...`.
Configurações da loja (taxa de entrega, chave Pix, margem de produção) ficam na tabela
`configuracoes`, lidas pelo helper `config_loja()`.

## Autenticação e login social

Login unificado pela lib **auth-multi** (`auth_multi`). No `.env` definir `AUTH_MULTI_MODO`
(este delivery usa `admin_cliente`) e as credenciais de cada provedor social
(`AUTH_MULTI_{PROVEDOR}_HABILITADO/CLIENT_ID/CLIENT_SECRET/REDIRECT`). O admin acessa por
`/admin`; o cliente por `/login`. Usuários ficam na tabela `usuarios` (do auth-multi); o
cliente tem sua tabela própria `clientes` vinculada ao usuário.

## Segurança implementada

- Senhas e chave de segurança do cliente com hash (`Hash::make`); nunca em código
- Cartões: guardamos apenas apelido, bandeira e 4 últimos dígitos (nunca o número completo)
- Áreas protegidas pelos guards/middlewares do auth-multi (`auth.multi:admin` e `auth.multi:cliente`)
- Verificação de propriedade em todos os endpoints de endereço/cartão/pedido
- CSRF em todas as requisições AJAX (com retry automático em 419 no `loja.js`)
- Auditoria em duas camadas com restauração por ponto no tempo (exige `MASTER_SENHA`)

## Comandos artisan úteis

```bash
php artisan admin:senha            # cadastra/redefine o acesso do painel (email+senha com hash)
php artisan auditoria:sincronizar  # (re)gera os gatilhos de auditoria de todas as tabelas
php artisan auditoria:ver          # histórico de auditoria (filtros --tabela/--acao/--registro/--limite)
php artisan auditoria:restaurar    # volta um registro ao estado exato de um evento (exige MASTER_SENHA)
php artisan migrate --seed         # cria o banco e popula textos/configurações/categorias
php artisan dusk --browse          # testa visualmente no navegador (obrigatório antes de commit)
```

## NF-e / DANFE

As libs `nfephp-org/sped-nfe` (emissão) e `nfephp-org/sped-da` (PDF/DANFE) já estão
instaladas para uso quando a emissão fiscal for ativada. Requerem certificado digital A1
e configuração de ambiente SEFAZ. Obs.: gerar PDF localmente exige a extensão `ext-gd`
(a hospedagem InfinityFree possui GD).

## Testes

```bash
php artisan test
```
