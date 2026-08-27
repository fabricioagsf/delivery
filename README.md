# Gostosuras — Loja de sobremesas com delivery e painel administrativo

Loja online de doces artesanais (brigadeiros, chocolates, gostosuras) com:

- **Vitrine** com categorias, destaques e carrinho via AJAX (JavaScript puro, sem frameworks)
- **Checkout** com Pix, cartão ou dinheiro; entrega em casa ou retirada na loja
- **Conta do cliente** num menu lateral expansível: dados pessoais, endereços, cartões,
  histórico de pedidos e **chave de segurança** (informada pelo cliente no recebimento
  para validar a entrega)
- **Painel administrativo** (`/admin`) com dashboard, pedidos, estoque e relatórios

## Stack

PHP 8.3 · Laravel 13 · MySQL · HTML · CSS puro (sem Bootstrap/Tailwind) · AJAX vanilla.
Todas as bibliotecas ficam fixadas em `composer.json` com `"platform": {"php": "8.3.0"}`
para compatibilidade com a hospedagem InfinityFree.

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

## Painel de administração

| Página | Conteúdo |
| --- | --- |
| Dashboard | Faturamento/pedidos hoje e no mês, ticket médio, gráfico dos últimos 14 dias, pedidos recentes, estoque crítico |
| Pedidos | Lista filtrável por status/cliente/código, alteração de status (novo → em preparo → em entrega → entregue / cancelado), detalhe completo com troco e lembrete da chave de segurança |
| Produtos e estoque | Busca, filtros (baixo/esgotado), ajuste rápido de estoque e mínimo, liga/desliga vitrine e destaque |
| Relatórios | Período personalizável com abas: vendas por dia, produtos mais vendidos, **previsão de produção por horário**, pagamentos, entregas × retiradas, estoque crítico |

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

## Segurança implementada

- Senhas e chave de segurança do cliente com hash (`Hash::make`)
- Cartões: guardamos apenas apelido, bandeira e 4 últimos dígitos (nunca o número completo)
- Guard dedicado `cliente` (tabela própria), separado do admin (`users`)
- Verificação de propriedade em todos os endpoints de endereço/cartão/pedido
- CSRF em todas as requisições AJAX

## NF-e / DANFE

As libs `nfephp-org/sped-nfe` (emissão) e `nfephp-org/sped-da` (PDF/DANFE) já estão
instaladas para uso quando a emissão fiscal for ativada. Requerem certificado digital A1
e configuração de ambiente SEFAZ. Obs.: gerar PDF localmente exige a extensão `ext-gd`
(a hospedagem InfinityFree possui GD).

## Testes

```bash
php artisan test
```
