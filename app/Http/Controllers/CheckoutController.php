<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Services\Carrinho as CarrinhoService;
use App\Services\Efi;
use App\Services\MercadoPago;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(protected CarrinhoService $carrinho) {}

    public function index()
    {
        if ($this->carrinho->contagem() === 0) {
            return redirect()
                ->route('carrinho.index')
                ->with('carrinho_vazio', texto('checkout', 'aviso.carrinho_vazio', 'Seu carrinho está vazio — escolha algumas gostosuras antes de finalizar.'));
        }

        /** @var Cliente|null $cliente */
        $cliente = auth('cliente')->user();

        $itens = $this->carrinho->itens();
        $itensMudaram = collect($itens)->filter(fn ($item) => $item['preco_mudou']);

        return view('checkout.index', [
            'itens' => $itens,
            'itensMudaram' => $itensMudaram,
            'subtotal' => $this->carrinho->subtotal(),
            'taxaEntrega' => (float) config_loja('taxa_entrega', '0'),
            'cliente' => $cliente,
            'enderecos' => $cliente?->enderecos ?? collect(),
            'cartoes' => $cliente?->cartoes ?? collect(),
            'cartaoMpAtivo' => app(MercadoPago::class)->disponivel(),
            'pixEfiAtivo' => app(Efi::class)->disponivel(),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->carrinho->contagem() === 0) {
            return redirect()
                ->route('carrinho.index')
                ->with('carrinho_vazio', texto('checkout', 'aviso.carrinho_vazio', 'Seu carrinho está vazio — escolha algumas gostosuras antes de finalizar.'));
        }

        $dados = $request->validate($this->regras(), $this->mensagens(), $this->atributos());

        $subtotal = $this->carrinho->subtotal();
        $tipoEntrega = $dados['tipo_entrega'];
        $taxaEntrega = $tipoEntrega === 'entrega'
            ? (float) config_loja('taxa_entrega', '0')
            : 0.0;

        [$enderecoId, $snapshot] = $this->resolverEndereco($request, $dados);
        $cartaoId = $this->resolverCartao($request, $dados);

        $pedido = DB::transaction(function () use ($dados, $subtotal, $taxaEntrega, $tipoEntrega, $enderecoId, $cartaoId, $snapshot) {
            $cliente = auth('cliente')->user();

            // Revalida o carrinho com trava de banco para nunca vender sem estoque
            $itensSessao = $this->carrinho->itens();

            if (empty($itensSessao)) {
                throw new HttpResponseException(
                    redirect()
                        ->route('carrinho.index')
                        ->with('carrinho_vazio', texto('checkout', 'aviso.carrinho_vazio', 'Seu carrinho está vazio — escolha algumas gostosuras antes de finalizar.'))
                );
            }

            $produtosTravados = Produto::query()
                ->whereIn('id', collect($itensSessao)->pluck('produto.id'))
                ->where('ativo', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($itensSessao as $item) {
                $produto = $produtosTravados[$item['produto']->id] ?? null;

                if (! $produto || ! $produto->temEstoque($item['quantidade'])) {
                    throw new HttpResponseException(
                        redirect()
                            ->route('carrinho.index')
                            ->with('erro_estoque', $item['produto']->nome)
                    );
                }

                if ($produto->estoque !== null) {
                    $produto->decrement('estoque', $item['quantidade']);
                }
            }

            $pedido = Pedido::create([
                ...$snapshot,
                'codigo' => $this->gerarCodigo(),
                'cliente_id' => $cliente?->id,
                'endereco_id' => $enderecoId,
                'cartao_id' => $cartaoId,
                'nome_cliente' => $dados['nome_cliente'],
                'telefone' => $dados['telefone'],
                'email' => $dados['email'] ?? ($cliente->email ?? null),
                'tipo_entrega' => $tipoEntrega,
                'forma_pagamento' => $dados['forma_pagamento'],
                'troco_para' => $dados['troco_para'] ?? null,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxaEntrega,
                'total' => $subtotal + $taxaEntrega,
                'observacoes' => $dados['observacoes'] ?? null,
                'status' => 'novo',
            ]);

            foreach ($itensSessao as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['produto']->id,
                    'nome_produto' => $item['produto']->nome,
                    'preco_unitario' => $item['produto']->preco,
                    'complementos' => $item['complementos'] ?: null,
                    'quantidade' => $item['quantidade'],
                ]);
            }

            return $pedido;
        });

        $this->carrinho->limpar();

        // Pagamento online: cria a cobrança e leva o cliente ao gateway.
        // Se o gateway falhar, o pedido JÁ está seguro — a tela de confirmação
        // oferece pagar de novo sem recriar o pedido nem mexer no estoque.
        if ($pedido->forma_pagamento === 'cartao_mp') {
            try {
                $preferencia = app(MercadoPago::class)->criarPreferencia($pedido);
                $pedido->update(['pagamento_status' => 'pendente', 'pagamento_id' => $preferencia['preferencia_id']]);

                return redirect()->away($preferencia['url']);
            } catch (\RuntimeException $e) {
                return redirect()
                    ->route('pedido.confirmacao', $pedido->codigo)
                    ->with('erro_pagamento', $e->getMessage());
            }
        }

        if ($pedido->forma_pagamento === 'pix_efi') {
            try {
                $cobranca = app(Efi::class)->criarCobranca($pedido);
                $pedido->update(['pagamento_status' => 'pendente', 'pagamento_id' => $cobranca['txid']]);
            } catch (\RuntimeException $e) {
                return redirect()
                    ->route('pedido.confirmacao', $pedido->codigo)
                    ->with('erro_pagamento', $e->getMessage());
            }
        }

        return redirect()
            ->route('pedido.confirmacao', $pedido->codigo)
            ->with('sucesso', true);
    }

    protected function regras(): array
    {
        $formas = ['pix', 'cartao', 'dinheiro'];
        if (app(MercadoPago::class)->disponivel()) {
            $formas[] = 'cartao_mp';
        }
        if (app(Efi::class)->disponivel()) {
            $formas[] = 'pix_efi';
        }

        $regras = [
            'nome_cliente' => ['required', 'string', 'min:3', 'max:150'],
            'telefone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'tipo_entrega' => ['required', 'in:entrega,retirada'],
            'forma_pagamento' => ['required', 'in:'.implode(',', $formas)],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'troco_para' => ['nullable', 'numeric', 'min:0'],
        ];

        if (request()->input('tipo_entrega') === 'entrega' && ! request()->filled('endereco_salvo_id')) {
            $regras += [
                'rua' => ['required', 'string', 'max:200'],
                'numero' => ['required', 'string', 'max:20'],
                'complemento' => ['nullable', 'string', 'max:120'],
                'bairro' => ['required', 'string', 'max:120'],
                'cidade' => ['required', 'string', 'max:120'],
                'cep' => ['nullable', 'string', 'max:12'],
            ];
        }

        if (auth('cliente')->check()) {
            $regras += [
                'endereco_salvo_id' => [
                    'nullable',
                    Rule::exists('enderecos', 'id')->where('cliente_id', auth('cliente')->id()),
                ],
                'cartao_salvo_id' => [
                    'nullable',
                    Rule::exists('cartoes', 'id')->where('cliente_id', auth('cliente')->id()),
                ],
            ];
        }

        return $regras;
    }

    protected function mensagens(): array
    {
        return [
            'required' => texto('checkout', 'erro.campo_obrigatorio', 'Preencha o campo :attribute.'),
            '*.in' => texto('checkout', 'erro.opcao_invalida', 'Escolha uma opção válida.'),
            '*.exists' => texto('checkout', 'erro.opcao_invalida', 'Escolha uma opção válida.'),
        ];
    }

    protected function atributos(): array
    {
        return [
            'nome_cliente' => texto('checkout', 'campo.nome_cliente', 'Nome completo'),
            'telefone' => texto('checkout', 'campo.telefone', 'Telefone / WhatsApp'),
            'email' => texto('checkout', 'campo.email', 'E-mail'),
            'rua' => texto('checkout', 'campo.rua', 'Rua'),
            'numero' => texto('checkout', 'campo.numero', 'Número'),
            'complemento' => texto('checkout', 'campo.complemento', 'Complemento'),
            'bairro' => texto('checkout', 'campo.bairro', 'Bairro'),
            'cidade' => texto('checkout', 'campo.cidade', 'Cidade'),
            'cep' => texto('checkout', 'campo.cep', 'CEP'),
            'observacoes' => texto('checkout', 'campo.observacoes', 'Observações'),
            'forma_pagamento' => texto('checkout', 'secao.pagamento', 'Pagamento'),
            'troco_para' => texto('checkout', 'campo.troco_para', 'Troco para'),
        ];
    }

    /**
     * @return array{0: int|null, 1: array<string, mixed>} [endereco_id, snapshot do endereço]
     */
    protected function resolverEndereco(Request $request, array $dados): array
    {
        if ($dados['tipo_entrega'] !== 'entrega') {
            return [null, []];
        }

        if (auth('cliente')->check() && ! empty($dados['endereco_salvo_id'])) {
            $endereco = auth('cliente')->user()->enderecos()->findOrFail($dados['endereco_salvo_id']);

            return [
                $endereco->id,
                [
                    'rua' => $endereco->rua,
                    'numero' => $endereco->numero,
                    'complemento' => $endereco->complemento,
                    'bairro' => $endereco->bairro,
                    'cidade' => $endereco->cidade,
                    'cep' => $endereco->cep,
                ],
            ];
        }

        return [
            null,
            [
                'rua' => $dados['rua'],
                'numero' => $dados['numero'],
                'complemento' => $dados['complemento'] ?? null,
                'bairro' => $dados['bairro'],
                'cidade' => $dados['cidade'],
                'cep' => $dados['cep'] ?? null,
            ],
        ];
    }

    protected function resolverCartao(Request $request, array $dados): ?int
    {
        if (($dados['forma_pagamento'] ?? null) !== 'cartao') {
            return null;
        }

        if (! auth('cliente')->check() || empty($dados['cartao_salvo_id'])) {
            return null;
        }

        return (int) auth('cliente')->user()->cartoes()->findOrFail($dados['cartao_salvo_id'])->id;
    }

    protected function gerarCodigo(): string
    {
        do {
            $codigo = 'DOC-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (Pedido::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
