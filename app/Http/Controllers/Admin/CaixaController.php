<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Services\Efi;
use App\Support\Pix;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Caixa — exclusivo para os pedidos das mesas.
 * Grade de mesas; clicar numa mesa mostra a conta (pedidos em aberto com
 * itens e o total). Fechar a conta registra a forma de pagamento, marca os
 * pedidos como entregues/pagos e a mesa volta a ficar livre.
 */
class CaixaController extends Controller
{
    public const FORMAS = ['dinheiro', 'pix', 'cartao'];

    public function index(): View|RedirectResponse
    {
        if (! modulo_ativo('pdv')) {
            return redirect()->route('admin.dashboard')
                ->with('erro_modulo_caixa', texto('admin_caixa', 'erro.desativado', 'O módulo PDV (mesas, tablet e caixa) está desligado. Para ativá-lo, mude o flag ativo para 1 na tabela modulos.'));
        }

        $mesas = Mesa::query()->ativas()->orderBy('id')->get();

        return view('admin.caixa', [
            'mesas' => $mesas,
        ]);
    }

    public function estado(): JsonResponse
    {
        if (! modulo_ativo('pdv')) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.desativado', 'O módulo PDV está desligado.')], 403);
        }

        $lojaId = loja_atual_id();
        $mesas = Mesa::query()->ativas()->orderBy('id')->get();

        $pedidosPorMesa = Pedido::query()
            ->where('loja_id', $lojaId)
            ->whereIn('mesa_id', $mesas->pluck('id'))
            ->contaAberta()
            ->orderBy('created_at')
            ->get(['id', 'mesa_id', 'codigo', 'status', 'total', 'created_at', 'nome_cliente', 'forma_pagamento']);

        $dados = $mesas->map(function (Mesa $mesa) use ($pedidosPorMesa) {
            $pedidos = $pedidosPorMesa->where('mesa_id', $mesa->id)->values();

            return [
                'id' => $mesa->id,
                'nome' => $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)),
                'capacidade' => $mesa->capacidade,
                'estado' => $pedidos->isNotEmpty() ? 'com_conta' : 'livre',
                'total' => (float) $pedidos->sum('total'),
                'qtd_pedidos' => $pedidos->count(),
            ];
        })->values();

        return response()->json(['mesas' => $dados]);
    }

    /**
     * Conta completa de UMA mesa (JSON): pedidos em aberto com itens e
     * complementos, pronto para a tela do caixa montar o fechamento.
     */
    public function conta(Mesa $mesa): JsonResponse
    {
        if (! modulo_ativo('pdv')) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.desativado', 'O módulo PDV está desligado.')], 403);
        }

        $pedidos = Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('mesa_id', $mesa->id)
            ->contaAberta()
            ->with(['itens' => fn ($q) => $q->orderBy('id')])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'mesa' => [
                'id' => $mesa->id,
                'nome' => $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)),
                'capacidade' => $mesa->capacidade,
                'estado' => $pedidos->isNotEmpty() ? 'com_conta' : 'livre',
                'total' => (float) $pedidos->sum('total'),
                'pedidos' => $pedidos->map(fn ($p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'status' => $p->status,
                    'cliente' => $p->nome_cliente,
                    'pagamento' => $p->forma_pagamento,
                    'observacoes' => $p->observacoes,
                    'quando' => optional($p->created_at)->format('H:i'),
                    'total' => (float) $p->total,
                    'itens' => $p->itens->map(fn ($i) => [
                        'nome' => $i->nome_produto,
                        'quantidade' => (int) $i->quantidade,
                        'complementos' => collect($i->complementos ?? [])
                            ->map(fn ($c) => trim($c['nome'] ?? ''))
                            ->filter()
                            ->values(),
                        'subtotal' => (float) $i->subtotal(),
                    ])->values(),
                ])->values(),
            ],
            'pix' => $this->dadosPix((float) $pedidos->sum('total')),
        ]);
    }

    /**
     * Dados para o bloco Pix do caixa: payload copia e cola (chave da loja)
     * e se a operadora Efí está disponível para o QR automático. Nunca
     * inventa valor — sem os configurados, retorna null e o JS avisa.
     */
    private function dadosPix(float $total): array
    {
        $valorPix = $total > 0 ? $total : null;

        $chave = trim((string) config_loja('chave_pix'));
        $nome = trim((string) config_loja('empresa_razao_social'));
        $cidade = trim((string) config_loja('empresa_cidade'));

        $payload = null;
        if ($valorPix !== null && $chave !== '' && $nome !== '' && $cidade !== '') {
            $payload = Pix::copiaECola($chave, $valorPix, $nome, $cidade);
        }

        return [
            'chave_payload' => $payload,
            'efi_disponivel' => app(Efi::class)->disponivel(),
            'efi_taxa' => trim((string) config_loja('efi_taxa')) !== ''
                ? (float) config_loja('efi_taxa')
                : null,
        ];
    }

    /**
     * Gera o QR Pix automático (Efí) da conta de uma mesa via API. O txid
     * é derivado da mesa (idempotente): chamadas repetidas devolvem a
     * mesma cobrança.
     */
    public function pixEfi(Mesa $mesa): JsonResponse
    {
        if (! modulo_ativo('pdv')) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.desativado', 'O módulo PDV está desligado.')], 403);
        }

        if (! app(Efi::class)->disponivel()) {
            return response()->json([
                'disponivel' => false,
                'mensagem' => texto('admin_caixa', 'pix.sem_efi', 'Pix automático (Efí) não ativado — em Configurações, ative a Efí e informe a chave de recebimento.'),
            ], 422);
        }

        $pedidos = Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('mesa_id', $mesa->id)
            ->contaAberta()
            ->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.sem_conta', 'Esta mesa não tem conta em aberto.')], 422);
        }

        try {
            $cobranca = app(Efi::class)->criarCobrancaConta(
                (string) $mesa->codigo,
                (string) ($mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id))),
                (float) $pedidos->sum('total')
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'disponivel' => true,
                'mensagem' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'disponivel' => true,
            'txid' => $cobranca['txid'],
            'copia_e_cola' => $cobranca['copia_e_cola'],
        ]);
    }

    public function fechar(Request $request, Mesa $mesa): JsonResponse
    {
        if (! modulo_ativo('pdv')) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.desativado', 'O módulo PDV está desligado.')], 403);
        }

        try {
            $dados = $request->validate([
                'forma_pagamento' => ['required', Rule::in(self::FORMAS)],
                'troco_para' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            ], [
                'forma_pagamento.required' => texto('admin_caixa', 'val.forma_obrigatoria', 'Escolha a forma de pagamento.'),
                'forma_pagamento.in' => texto('admin_caixa', 'val.forma_invalida', 'Forma de pagamento inválida.'),
                'troco_para.numeric' => texto('admin_caixa', 'val.troco_numero', 'O valor recebido deve ser numérico.'),
            ], [
                'forma_pagamento' => texto('admin_caixa', 'campo.forma_pagamento', 'Forma de pagamento'),
                'troco_para' => texto('admin_caixa', 'campo.troco_para', 'Valor recebido'),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensagem' => collect($e->errors())->flatten()->first()], 422);
        }

        $pedidos = Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('mesa_id', $mesa->id)
            ->contaAberta()
            ->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['mensagem' => texto('admin_caixa', 'erro.sem_conta', 'Esta mesa não tem conta em aberto.')], 422);
        }

        $troco = ($dados['forma_pagamento'] === 'dinheiro' && ! empty($dados['troco_para']))
            ? $dados['troco_para']
            : null;

        foreach ($pedidos as $pedido) {
            $pedido->update([
                'status' => 'entregue',
                'forma_pagamento' => $dados['forma_pagamento'],
                'pagamento_status' => 'pago',
                'troco_para' => $troco !== null ? number_format((float) $troco, 2, '.', '') : null,
            ]);
        }

        return response()->json([
            'mensagem' => texto('admin_caixa', 'sucesso.fechado', 'Conta da mesa fechada!'),
            'total' => (float) $pedidos->sum('total'),
        ]);
    }
}