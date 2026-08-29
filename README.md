# Gostosuras â€” Loja de sobremesas com delivery e painel administrativo

Loja online de doces artesanais (brigadeiros, chocolates, gostosuras) com:

- **Vitrine** com categorias, destaques e carrinho via AJAX (JavaScript puro, sem frameworks)
- **Complementos personalizÃ¡veis** (adiÃ§Ã£o paga ou remoÃ§Ã£o grÃ¡tis) por produto, escolhidos
  na hora do pedido
- **CardÃ¡pio digital pÃºblico** (`/cardapio`) com QR code, onde o cliente pede direto
- **Checkout** com Pix, cartÃ£o ou dinheiro; entrega em casa ou retirada na loja
- **Conta do cliente** num menu lateral expansÃ­vel: dados pessoais, endereÃ§os, cartÃµes,
  histÃ³rico de pedidos e **chave de seguranÃ§a** (informada pelo cliente no recebimento
  para validar a entrega)
- **Painel administrativo** (`/admin`) com dashboard, pedidos, estoque, relatÃ³rios e
  encaminhamento de pedidos ao cliente pelo WhatsApp

> **DocumentaÃ§Ã£o:** [Help â€” funÃ§Ãµes e configuraÃ§Ãµes](docs/HELP.md) Â·
> [DocumentaÃ§Ã£o tÃ©cnica (rotas/services/chaves)](docs/DOCUMENTACAO.md)

## Stack

PHP 8.3 Â· Laravel 13 Â· MySQL Â· HTML Â· CSS puro (sem Bootstrap/Tailwind) Â· AJAX vanilla.
Todas as bibliotecas ficam fixadas em `composer.json` com `"platform": {"php": "8.3.0"}`
para compatibilidade com a hospedagem InfinityFree.

Bibliotecas do usuÃ¡rio integradas:

- **`fabricioagsf/auth-multi`** â€” autenticaÃ§Ã£o unificada (admin e cliente) com login
  social nativo (Google / Facebook / Microsoft / Instagram) e multi-tenant por domÃ­nio
  (tabela `tenants`). Protege as Ã¡reas pelos middlewares `auth.multi:admin` e
  `auth.multi:cliente`.
- **`fabricioagsf/item-venda`** â€” mÃ³dulo de produtos/serviÃ§os (superset da tabela
  `produtos`). Controlado pela flag `item_venda_ativo` com tela prÃ³pria em
  `/admin/item-venda`.
- **`nfephp-org/sped-nfe`** + **`nfephp-org/sped-da`** â€” emissÃ£o de NF-e e PDF/DANFE
  (prontas para uso; veja "NF-e / DANFE").
- **`laravel/dusk`** (dev) â€” teste visual no navegador antes de cada commit.

## Como rodar

```bash
composer install
cp .env.example .env        # configure o MySQL em DB_* 
php artisan key:generate
php artisan migrate --seed
php artisan serve           # http://localhost:8000
```

### Criar o admin

UsuÃ¡rio e senha do painel vivem **somente no banco** (tabela `users`) â€” nunca em
cÃ³digo ou `.env`. Para cadastrar ou redefinir o acesso (pergunta e-mail e senha,
confere, e grava com hash):

```bash
php artisan admin:senha
```

Acesse `http://localhost:8000/admin`. Seeders nunca sobrescrevem o admin:
`AdminSeeder` apenas avisa se ainda nÃ£o existe acesso no banco.

## Regras de venda e estoque

- **Um produto sÃ³ Ã© vendido com quantidade maior que zero.** Sem quantidade
  definida (`estoque = NULL`) o produto fica **indisponÃ­vel** na vitrine
  (mostra "IndisponÃ­vel", sem botÃ£o de comprar).
- `estoque = 0` â†’ aparece como **"Esgotado"**, tambÃ©m sem botÃ£o de comprar.
- A quantidade Ã© revalidada no carrinho e de novo no checkout com
  `lockForUpdate` dentro da transaÃ§Ã£o do pedido; sem quantidade a venda Ã©
  bloqueada e o cliente Ã© avisado.
- Cancelar um pedido no admin devolve os itens ao estoque automaticamente.
- `estoque_minimo` alimenta os alertas de "estoque baixo" no dashboard e nos relatÃ³rios.
- **ServiÃ§o/serviÃ§os:** este projeto nÃ£o possui tabela de serviÃ§os; o equivalente
  (sÃ³ vende o que estÃ¡ **ativo**) jÃ¡ Ã© aplicado a produtos em vitrine, carrinho e
  checkout (`ativo = true` obrigatÃ³rio em todas as etapas).

## Complementos (personalizaÃ§Ãµes por produto)

Cada produto pode ter complementos de dois tipos, configurados no editor de produto:

- **Adicional**: permite ao cliente adicionar algo por um preÃ§o a mais (ex.: + brigadeiro R$ 2,00).
- **RemoÃ§Ã£o**: opÃ§Ã£o grÃ¡tis de retirar ingrediente (ex.: sem granulado).

No carrinho, cada combinaÃ§Ã£o de complementos gera uma **linha separada** (chave md5) para
faturamento e estoque corretos. O preÃ§o dos complementos Ã© sempre **recomputado do banco**
na renderizaÃ§Ã£o do carrinho e do checkout (regra de ouro â€” nunca preÃ§o congelado). O pedido
guarda o snapshot dos complementos escolhidos, exibido na confirmaÃ§Ã£o e no painel admin.

## CardÃ¡pio digital

Rota pÃºblica `GET /cardapio` (`CardapioController`): reÃºne os **produtos ativos** agrupados
pelas **categorias ativas** num layout de menu (`resources/views/cardapio/index.blade.php`),
com navegaÃ§Ã£o Ã¢ncora por categoria. O cliente **pede direto do cardÃ¡pio** usando o mesmo
fluxo de carrinho/checkout da loja (reaproveita `vitrine.partials.produto-card` e `loja.js`).

O link e o **QR code** do cardÃ¡pio aparecem na tela **ConfiguraÃ§Ãµes** do admin (seÃ§Ã£o
"CardÃ¡pio digital", fora do formulÃ¡rio): botÃ£o "Ver cardÃ¡pio", campo de URL copiÃ¡vel e QR
gerado por serviÃ§o externo `api.qrserver.com` (nenhuma dependÃªncia nova).

- Link "CardÃ¡pio" tambÃ©m estÃ¡ no menu superior da loja.
- SÃ³ aparecem categorias que tenham ao menos um produto ativo.

## Temas (identidade cultural)

A loja tem **5 temas** que trocam a **identidade cultural** (cores + nome + slogan +
rodapÃ© + herÃ³i + tÃ­tulo): **Guloseimas** (confeitaria, padrÃ£o), **Italiana**, **Japonesa**,
**Chinesa** e **Mexicana**. SeleÃ§Ã£o em **/admin/configuracoes â†’ "Tema da loja"** (`tema_loja`).
As paletas ficam em `public/css/themes/*.css` e a identidade de cada tema no grupo `tema`
da tabela `textos`. O **conteÃºdo do cardÃ¡pio** Ã© gerido pelo cadastro de produtos (a
regionalidade de conteÃºdo serÃ¡ tratada pelas filiais/multi-lojas â€” veja o roadmap).

## Cupons de desconto

A loja aceita **cupons de desconto** (percentual ou fixo) que o cliente aplica no
checkout, com restriÃ§Ãµes de **valor mÃ­nimo**, **limite de uso** e **validade**. GestÃ£o em
**/admin/cupons**. Um cupom ativo e vigente pode virar **promoÃ§Ã£o em destaque** na
vitrine, no cardÃ¡pio e como aviso no checkout (config `cupom_destaque`). O desconto Ã©
validado de novo no servidor ao fechar o pedido e o total vira
`subtotal + taxa âˆ’ desconto` (nunca negativo). Detalhes em
[docs/HELP.md](docs/HELP.md#10-cupons-de-desconto).

## Fidelidade (pontos)

Cliente **logado** acumula **pontos** a cada pedido (R$ 1,00 de subtotal rende
`fidelidade_ganho` ponto, configurÃ¡vel e padronizado em 1) e pode trocÃ¡-los por
**desconto no checkout** (config `fidelidade_ponto_valor` = valor em R$ de cada ponto,
padrÃ£o 0,10 â†’ 10 pontos = R$ 1,00 de desconto). GestÃ£o e liga/desliga (captura + botÃ£o
**Ativar**) em **/admin/fidelidade**. Os pontos sÃ£o **creditados** ao criar o pedido e os
resgatados sÃ£o **abatidos** do saldo do cliente na mesma transaÃ§Ã£o; o desconto por pontos
nunca passa do subtotal e soma ao desconto de cupom. Detalhes em
[docs/HELP.md](docs/HELP.md#11-fidelidade-pontos).

## Painel de administraÃ§Ã£o

| PÃ¡gina | ConteÃºdo |
| --- | --- |
| Dashboard | Faturamento/pedidos hoje e no mÃªs, ticket mÃ©dio, grÃ¡fico dos Ãºltimos 14 dias, pedidos recentes, estoque crÃ­tico |
| Pedidos | Lista filtrÃ¡vel por status/cliente/cÃ³digo, alteraÃ§Ã£o de status (novo â†’ em preparo â†’ em entrega â†’ entregue / cancelado), detalhe completo com troco, lembrete da chave de seguranÃ§a e **encaminhamento ao cliente pelo WhatsApp** (API ou link `wa.me`) |
| Produtos e estoque | Busca, filtros (baixo/esgotado), ajuste rÃ¡pido de estoque e mÃ­nimo, liga/desliga vitrine e destaque, **complementos do produto** |
| ConfiguraÃ§Ãµes | Loja (taxa de entrega, chave Pix, margem de produÃ§Ã£o, **tema da loja**), NF-e, WhatsApp Cloud API, login social, pagamento online, mÃ³dulo item-venda e **CardÃ¡pio digital (link + QR)** |
| Clientes | Contas, mÃ©tricas e redefiniÃ§Ã£o de senha (envio via WhatsApp) |
| RelatÃ³rios | PerÃ­odo personalizÃ¡vel com abas: vendas por dia, produtos mais vendidos, **previsÃ£o de produÃ§Ã£o por horÃ¡rio**, pagamentos, entregas Ã— retiradas, estoque crÃ­tico |
| Banners | CRUD com agendamento automÃ¡tico (entra/sai do ar sozinho) |
| Auditoria | HistÃ³rico de tudo criado/alterado/excluÃ­do no banco + restauraÃ§Ã£o por ponto no tempo (exige senha master) |
| PWA / App | MÃ³dulo PWA: ativa/desativa o cardÃ¡pio offline e a instalaÃ§Ã£o, mostra nÂº de imagens guardadas, links do service worker/manifesto e **renova o cache** dos clientes |
| Cupons / PromoÃ§Ãµes | CRUD de cupons de desconto (percentual ou fixo) com restriÃ§Ãµes (valor mÃ­nimo, limite de uso e validade) e **promoÃ§Ã£o em destaque** na vitrine/cardÃ¡pio |

### PWA (app / cardÃ¡pio offline)

O delivery Ã© uma **PWA** (JS puro, sem biblioteca externa). O cliente visita o
**cardÃ¡pio** (`/cardapio`) uma vez e depois o **consulta sem internet** (Service Worker
guarda HTML, CSS/JS e as imagens dos produtos/banners ativos), alÃ©m de poder **instalar** um
atalho na tela inicial do celular. GestÃ£o em `/admin/pwa` (menu "PWA / App"). Veja
[docs/HELP.md](docs/HELP.md#6-mÃ³dulo-pwa-app--cardÃ¡pio-offline) para detalhes.

### MÃ©trica de produÃ§Ã£o por horÃ¡rio

RelatÃ³rio *HorÃ¡rios*: soma os itens vendidos em cada hora do dia no perÃ­odo escolhido,
divide pelos dias que tiveram venda (mÃ©dia diÃ¡ria) e aplica margem de seguranÃ§a da
configuraÃ§Ã£o `margem_producao` (%). Resultado: quantos itens produzir em cada faixa
de horÃ¡rio. Ajuste a margem em `configuracoes.margem_producao`.

Todas as queries agregadas sÃ£o compatÃ­veis com MySQL/MariaDB em modo `ONLY_FULL_GROUP_BY`.

## Textos da interface

Nenhum texto Ã© fixo no cÃ³digo: tudo vem da tabela `textos` (`pagina`, `chave`, `valor`)
via helper global `texto('pagina', 'chave', 'fallback')`, semeada pelo
`TextoSistemaSeeder`. Para mudar um texto: `UPDATE textos SET valor = ...`.
ConfiguraÃ§Ãµes da loja (taxa de entrega, chave Pix, margem de produÃ§Ã£o) ficam na tabela
`configuracoes`, lidas pelo helper `config_loja()`.

## AutenticaÃ§Ã£o e login social

Login unificado pela lib **auth-multi** (`auth_multi`). No `.env` definir `AUTH_MULTI_MODO`
(este delivery usa `admin_cliente`) e as credenciais de cada provedor social
(`AUTH_MULTI_{PROVEDOR}_HABILITADO/CLIENT_ID/CLIENT_SECRET/REDIRECT`). O admin acessa por
`/admin`; o cliente por `/login`. UsuÃ¡rios ficam na tabela `usuarios` (do auth-multi); o
cliente tem sua tabela prÃ³pria `clientes` vinculada ao usuÃ¡rio.

## SeguranÃ§a implementada

- Senhas e chave de seguranÃ§a do cliente com hash (`Hash::make`); nunca em cÃ³digo
- CartÃµes: guardamos apenas apelido, bandeira e 4 Ãºltimos dÃ­gitos (nunca o nÃºmero completo)
- Ãreas protegidas pelos guards/middlewares do auth-multi (`auth.multi:admin` e `auth.multi:cliente`)
- VerificaÃ§Ã£o de propriedade em todos os endpoints de endereÃ§o/cartÃ£o/pedido
- CSRF em todas as requisiÃ§Ãµes AJAX (com retry automÃ¡tico em 419 no `loja.js`)
- Auditoria em duas camadas com restauraÃ§Ã£o por ponto no tempo (exige `MASTER_SENHA`)

## Comandos artisan Ãºteis

```bash
php artisan admin:senha            # cadastra/redefine o acesso do painel (email+senha com hash)
php artisan auditoria:sincronizar  # (re)gera os gatilhos de auditoria de todas as tabelas
php artisan auditoria:ver          # histÃ³rico de auditoria (filtros --tabela/--acao/--registro/--limite)
php artisan auditoria:restaurar    # volta um registro ao estado exato de um evento (exige MASTER_SENHA)
php artisan migrate --seed         # cria o banco e popula textos/configuraÃ§Ãµes/categorias
php artisan dusk --browse          # testa visualmente no navegador (obrigatÃ³rio antes de commit)
```

## NF-e / DANFE

As libs `nfephp-org/sped-nfe` (emissÃ£o) e `nfephp-org/sped-da` (PDF/DANFE) jÃ¡ estÃ£o
instaladas para uso quando a emissÃ£o fiscal for ativada. Requerem certificado digital A1
e configuraÃ§Ã£o de ambiente SEFAZ. Obs.: gerar PDF localmente exige a extensÃ£o `ext-gd`
(a hospedagem InfinityFree possui GD).

## Testes

```bash
php artisan test
```
