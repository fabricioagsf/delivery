<?php

use App\Models\Configuracao;
use App\Models\Texto;
use Fabricioagsf\AuthMulti\Models\Tenant;

if (! function_exists('loja_atual')) {
    /**
     * Loja ativa da sessão (ou a primeira loja ativa como padrão).
     * Em console/webhooks sem sessão, devolve a loja padrão.
     */
    function loja_atual(): ?Tenant
    {
        static $loja = false;

        if ($loja === false) {
            $loja = null;

            if (session()->isStarted() && session('loja_id')) {
                $loja = Tenant::find((int) session('loja_id'));
            }

            if (! $loja || $loja->status !== 'ativo') {
                $loja = Tenant::where('status', 'ativo')->orderBy('id')->first();

                if ($loja && session()->isStarted()) {
                    session(['loja_id' => $loja->id]);
                }
            }
        }

        return $loja ?: null;
    }
}

if (! function_exists('loja_atual_id')) {
    /** Id da loja ativa (ou null quando não há nenhuma loja). */
    function loja_atual_id(): ?int
    {
        return loja_atual()?->id;
    }
}

if (! function_exists('lojas_ativas')) {
    /** Lojas ativas (usado pelo seletor de loja da loja e do painel). */
    function lojas_ativas(): \Illuminate\Support\Collection
    {
        return Tenant::where('status', 'ativo')->orderBy('id')->get();
    }
}

if (! function_exists('mostrar_seletor_loja')) {
    /**
     * Indica se o seletor de loja (filial) deve aparecer na vitrine.
     * Só aparece quando existe mais de uma loja ativa cadastrada E o cadastro
     * de filiais não é feito por domínio (nesse caso cada domínio já resolve
     * a própria loja, então o seletor ficaria confuso).
     */
    function mostrar_seletor_loja(): bool
    {
        return lojas_ativas()->count() > 1 && ! config('auth-multi.tenant_por_dominio');
    }
}

if (! function_exists('texto')) {
    /**
     * Retorna um texto da interface a partir da tabela `textos`.
     * Prioriza o valor da loja ativa; sem ele, usa o global (loja_id NULL);
     * sem nenhum, o fallback é exibido (e semeado) quando a chave não existe.
     *
     * Resiliente: se o banco estiver indisponível, devolve o fallback
     * em vez de lançar um segundo erro por cima do primeiro.
     */
    function texto(string $pagina, string $chave, string $fallback): string
    {
        static $cache = [];

        $chaveCache = (string) loja_atual_id().'|'.$pagina.'|'.$chave;

        if (array_key_exists($chaveCache, $cache)) {
            return $cache[$chaveCache];
        }

        try {
            $registro = Texto::query()
                ->where('pagina', $pagina)
                ->where('chave', $chave)
                ->where(fn ($q) => $q->where('loja_id', loja_atual_id())->orWhereNull('loja_id'))
                ->orderByRaw('loja_id IS NULL')
                ->first();

            $cache[$chaveCache] = $registro?->valor ?? $fallback;
        } catch (Throwable) {
            $cache[$chaveCache] = $fallback;
        }

        return $cache[$chaveCache];
    }
}

if (! function_exists('config_loja')) {
    /**
     * Retorna uma configuração da loja (tabela `configuracoes`).
     * Prioriza o valor da loja ativa; sem ele, usa o global (loja_id NULL).
     */
    function config_loja(string $chave, ?string $fallback = null): ?string
    {
        static $cache = [];

        $chaveCache = (string) loja_atual_id().'|'.$chave;

        if (array_key_exists($chaveCache, $cache)) {
            return $cache[$chaveCache];
        }

        $valor = Configuracao::query()
            ->where('chave', $chave)
            ->where(fn ($q) => $q->where('loja_id', loja_atual_id())->orWhereNull('loja_id'))
            ->orderByRaw('loja_id IS NULL')
            ->value('valor');

        $cache[$chaveCache] = $valor ?? $fallback;

        return $cache[$chaveCache];
    }
}

if (! function_exists('preco_br')) {
    /**
     * Formata um valor decimal do banco como moeda brasileira.
     */
    function preco_br($valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}

if (! function_exists('status_pedido')) {
    /**
     * Rótulo do status do pedido (tabela textos, página 'status').
     */
    function status_pedido(string $status): string
    {
        return texto('status', 'status.'.$status, ucfirst(str_replace('_', ' ', $status)));
    }
}

if (! function_exists('status_pilula')) {
    /**
     * Modificador de classe CSS da pílula de status (kebab-case).
     */
    function status_pilula(string $status): string
    {
        return str_replace('_', '-', $status);
    }
}

if (! function_exists('forma_pagamento_label')) {
    /**
     * Rótulo da forma de pagamento (tabela textos, página 'pagamentos').
     * NULL = a forma ainda não foi definida (pedido de mesa sem fechamento).
     */
    function forma_pagamento_label(?string $forma): string
    {
        if ($forma === null || $forma === '') {
            return texto('pagamentos', 'forma.indefinido', 'A definir');
        }

        return texto('pagamentos', 'forma.'.$forma, ucfirst($forma));
    }
}

if (! function_exists('detectar_bandeira')) {
    /**
     * Detecta a bandeira de um cartão pelos dígitos iniciais.
     */
    function detectar_bandeira(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);

        $regras = [
            'Visa' => '/^4/',
            'Mastercard' => '/^(5[1-5]|2[2-7])/',
            'American Express' => '/^3[47]/',
            'Elo' => '/^(4011|4312|4389|4514|4576|5041|5066|5067|509|627780|636297|6362|6363|650|6516|6550)/',
            'Hipercard' => '/^(606282|3841)/',
            'Diners Club' => '/^3(?:0[0-5]|[68])/',
            'Discover' => '/^(6011|65|64[4-9])/',
            'Aura' => '/^50/',
        ];

        foreach ($regras as $bandeira => $padrao) {
            if (preg_match($padrao, $numero)) {
                return $bandeira;
            }
        }

        return 'Outro';
    }
}

if (! function_exists('tema_ativo')) {
    /** Retorna o id do tema ativo da loja (config `tema_loja`). */
    function tema_ativo(): string
    {
        return \App\Support\Temas::ativo();
    }
}

if (! function_exists('tema_css')) {
    /** Retorna o caminho (relativo a public/) da CSS de paleta do tema ativo, ou null (usa a base). */
    function tema_css(): ?string
    {
        return \App\Support\Temas::css();
    }
}

if (! function_exists('tema_texto')) {
    /**
     * Texto de identidade do tema ativo (grupo `tema`, chave `{tema}.{chave}`),
     * com fallback para o texto base da loja quando não existir valor do tema.
     */
    function tema_texto(string $chave, string $fallback): string
    {
        $tema = \App\Support\Temas::ativo();

        return texto('tema', $tema.'.'.$chave, $fallback);
    }
}

if (! function_exists('mesa_sessao')) {
    /**
     * Mesa ativa do cliente atual (sessão).
     * Definida quando o cliente abre o cardápio a partir de um QR code de mesa
     * (`/cardapio?mesa=ID`) e persistida até o pedido ser finalizado.
     */
    function mesa_sessao(): ?\App\Models\Mesa
    {
        static $mesa = false;

        if ($mesa === false) {
            $id = session('mesa_id');
            $mesa = $id ? \App\Models\Mesa::find((int) $id) : null;
        }

        return $mesa ?: null;
    }
}

if (! function_exists('mesa_sessao_id')) {
    /** ID da mesa ativa na sessão, ou null quando não há. */
    function mesa_sessao_id(): ?int
    {
        return mesa_sessao()?->id;
    }
}

if (! function_exists('modulo_ativo')) {
    /**
     * Indica se um módulo está ligado (flag `ativo` = 1 na tabela `modulos`).
     * A ativação é feita APENAS direto no banco; este helper só lê o estado.
     * Prioriza a linha da loja ativa; sem ela, usa a global (loja_id NULL).
     */
    function modulo_ativo(string $slug): bool
    {
        static $cache = [];

        if (array_key_exists($slug, $cache)) {
            return $cache[$slug];
        }

        try {
            $modulo = \App\Models\Modulo::query()
                ->where('slug', $slug)
                ->where(fn ($q) => $q->where('loja_id', loja_atual_id())->orWhereNull('loja_id'))
                ->orderByRaw('loja_id IS NULL')
                ->first();

            $cache[$slug] = (bool) ($modulo?->ativo ?? false);
        } catch (Throwable) {
            $cache[$slug] = false;
        }

        return $cache[$slug];
    }
}

if (! function_exists('canal_atual')) {
    /**
     * Canal de venda em vigor na sessão de compra:
     * 'pdv' quando o cliente está no fluxo de mesa (QR/tablet), 'delivery'
     * quando está navegando online (site). Determina qual módulo vale.
     */
    function canal_atual(): string
    {
        return mesa_sessao_id() !== null ? 'pdv' : 'delivery';
    }
}

if (! function_exists('modulo_off_view')) {
    /**
     * Página pública de aviso quando um canal (módulo) está desligado no banco.
     * 'delivery' = vendas online; 'pdv' = atendimento na mesa (QR/tablet).
     */
    function modulo_off_view(string $canal): \Illuminate\Contracts\View\View
    {
        $ehMesa = $canal === 'pdv';

        return view('erros.modulo_off', [
            'codigo' => $ehMesa ? 'PDV' : 'DELIVERY',
            'titulo' => texto('modulo_off', $ehMesa ? 'mesa.titulo' : 'delivery.titulo', $ehMesa ? 'Atendimento na mesa desativado' : 'Vendas online desativadas'),
            'mensagem' => texto('modulo_off', $ehMesa ? 'mesa.texto' : 'delivery.texto', $ehMesa ? 'O atendimento pela mesa está desativado nesta unidade. Faça o seu pedido pelo delivery online.' : 'As vendas online estão desativadas nesta unidade. Faça o seu pedido na mesa ou volte em breve.'),
        ]);
    }
}

if (! function_exists('modulo_off_json')) {
    /** Resposta JSON (403) quando o canal da sessão está desligado. */
    function modulo_off_json(string $canal): \Illuminate\Http\JsonResponse
    {
        $ehMesa = $canal === 'pdv';

        return response()->json([
            'mensagem' => $ehMesa
                ? texto('modulo_off', 'mesa.titulo', 'Atendimento na mesa desativado')
                : texto('modulo_off', 'delivery.titulo', 'Vendas online desativadas'),
        ], 403);
    }
}

if (! function_exists('promo_destaque')) {
    /**
     * Cupom promocional destacado na vitrine/cardápio (config `cupom_destaque`),
     * ou null quando não há destaque válido no momento.
     */
    function promo_destaque(): ?\App\Models\Cupom
    {
        $codigo = config_loja('cupom_destaque');

        if (! $codigo) {
            return null;
        }

        $cupom = \App\Models\Cupom::query()->where('codigo', $codigo)->first();

        if (! $cupom || ! $cupom->ativo || ! $cupom->vigente() || ! $cupom->temUsosDisponiveis()) {
            return null;
        }

        return $cupom;
    }
}
