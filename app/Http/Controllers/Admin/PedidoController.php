<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use App\Services\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public const STATUS = ['novo', 'em_preparo', 'em_entrega', 'entregue', 'cancelado'];

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $tipo = $request->query('tipo');
        $busca = $request->query('q');

        $pedidos = Pedido::query()
            ->withCount('itens')
            ->when(in_array($status, self::STATUS, true), fn ($q) => $q->where('status', $status))
            ->when(in_array($tipo, ['entrega', 'retirada'], true), fn ($q) => $q->where('tipo_entrega', $tipo))
            ->when($busca, fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'like', "%{$busca}%")
                ->orWhere('nome_cliente', 'like', "%{$busca}%")
                ->orWhere('telefone', 'like', "%{$busca}%")))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $contagemStatus = Pedido::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupByRaw('status')
            ->pluck('total', 'status');

        return view('admin.pedidos', [
            'pedidos' => $pedidos,
            'contagemStatus' => $contagemStatus,
            'statusAtual' => $status,
            'tipoAtual' => $tipo,
            'busca' => $busca,
            'statusLista' => self::STATUS,
        ]);
    }

    public function show(Pedido $pedido): View
    {
        return view('admin.pedidos_detalhe', [
            'pedido' => $pedido->load(['itens', 'cliente']),
        ]);
    }

    public function atualizarStatus(Request $request, Pedido $pedido): JsonResponse
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(self::STATUS)],
        ]);

        $novoStatus = $dados['status'];
        $eraCancelado = $pedido->status === 'cancelado';

        // Ao cancelar um pedido que não estava cancelado, devolve os itens ao
        // estoque da loja onde o pedido foi feito.
        if ($novoStatus === 'cancelado' && ! $eraCancelado) {
            $lojaId = $pedido->loja_id;
            foreach ($pedido->itens as $item) {
                if ($item->produto_id !== null && $lojaId !== null) {
                    ProdutoEstoque::where('produto_id', $item->produto_id)
                        ->where('loja_id', $lojaId)
                        ->whereNotNull('estoque')
                        ->increment('estoque', $item->quantidade);
                }
            }
        }

        $pedido->update(['status' => $novoStatus]);

        return response()->json([
            'mensagem' => texto('admin_pedidos', 'sucesso.status', 'Status atualizado!'),
            'status' => $novoStatus,
        ]);
    }

    /**
     * Gera (registra) a nota fiscal do pedido.
     * Com a emissão habilitada e a empresa configurada, a nota nasce PENDENTE;
     * a transmissão real à SEFAZ exige certificado digital A1 no .env.
     */
    public function gerarNota(Pedido $pedido): RedirectResponse
    {
        if (config_loja('emitir_nfe') !== '1') {
            return back()->with('erro_nota', texto('admin_pedidos', 'nota.desligada', 'A emissão de nota está desligada — ative em Configurações.'));
        }

        if (trim((string) config_loja('empresa_cnpj')) === '') {
            return back()->with('erro_nota', texto('admin_pedidos', 'nota.sem_empresa', 'Preencha o CNPJ da empresa em Configurações antes de gerar a nota.'));
        }

        if ($pedido->notas()->where('status', '!=', 'cancelada')->exists()) {
            return back()->with('erro_nota', texto('admin_pedidos', 'nota.ja_existe', 'Este pedido já tem uma nota fiscal registrada.'));
        }

        $certificadoConfigurado = env('NFE_CERT_PATH') && env('NFE_CERT_SENHA');

        NotaFiscal::create([
            'pedido_id' => $pedido->id,
            'modelo' => 'nfe',
            'status' => 'pendente',
            'mensagem' => $certificadoConfigurado
                ? texto('admin_pedidos', 'nota.pendente_cert', 'Pendente de transmissão à SEFAZ.')
                : texto('admin_pedidos', 'nota.pendente_sem_cert', 'Pendente: configure o certificado digital A1 (NFE_CERT_PATH e NFE_CERT_SENHA no .env) para transmitir.'),
        ]);

        return back()->with('sucesso_nota', texto('admin_pedidos', 'nota.criada', 'Nota fiscal registrada como PENDENTE para este pedido.'));
    }

    /**
     * Encaminha o resumo do pedido ao cliente via WhatsApp.
     * Usa a Cloud API quando configurada; senão, devolve um link wa.me
     * pré-preenchido para o lojista abrir no navegador/janela do WhatsApp.
     */
    public function enviarWhatsApp(Pedido $pedido): RedirectResponse
    {
        $telefone = $pedido->telefone;

        if (empty($telefone)) {
            return back()->with('erro_whatsapp', texto('admin_pedidos', 'whatsapp.sem_telefone', 'Este pedido não tem telefone de contato.'));
        }

        $whatsapp = app(WhatsApp::class);
        $mensagem = $this->montarMensagemPedido($pedido);

        if (! $whatsapp->disponivel()) {
            $link = $whatsapp->linkWhatsapp($telefone, $mensagem);

            return back()
                ->with('whatsapp_link', $link)
                ->with('sucesso_whatsapp', texto('admin_pedidos', 'whatsapp.link_gerado', 'WhatsApp não configurado — abra o link abaixo para enviar o pedido.'));
        }

        $resultado = $whatsapp->enviarTexto($telefone, $mensagem);

        if ($resultado['ok']) {
            return back()->with('sucesso_whatsapp', texto('admin_pedidos', 'whatsapp.enviado', 'Pedido encaminhado ao cliente pelo WhatsApp!'));
        }

        $link = $whatsapp->linkWhatsapp($telefone, $mensagem);

        return back()
            ->with('whatsapp_link', $link)
            ->with('erro_whatsapp', ($resultado['erro'] ?? '').' '.texto('admin_pedidos', 'whatsapp.fallback', 'Use o link abaixo para enviar manualmente.'));
    }

    protected function montarMensagemPedido(Pedido $pedido): string
    {
        $linhas = [];

        $linhas[] = '*'.texto('admin_pedidos', 'whatsapp.pedido_rotulo', 'Pedido').': '.$pedido->codigo.'*';
        $linhas[] = texto('admin_pedidos', 'whatsapp.cliente', 'Cliente').': '.$pedido->nome_cliente;
        $linhas[] = texto('admin_pedidos', 'whatsapp.telefone', 'Telefone').': '.$pedido->telefone;
        $linhas[] = texto('admin_pedidos', 'whatsapp.tipo', 'Tipo').': '.
            ($pedido->tipo_entrega === 'entrega'
                ? texto('confirmacao', 'entrega.titulo', 'Entrega')
                : texto('confirmacao', 'retirada.titulo', 'Retirada'));
        $linhas[] = texto('admin_pedidos', 'whatsapp.pagamento', 'Pagamento').': '.forma_pagamento_label($pedido->forma_pagamento);

        if ($pedido->tipo_entrega === 'entrega') {
            $linhas[] = texto('admin_pedidos', 'whatsapp.endereco', 'Endereço').': '.
                $pedido->rua.', '.$pedido->numero.($pedido->complemento ? ' — '.$pedido->complemento : '').
                ' — '.$pedido->bairro.' '.$pedido->cidade;
        }

        $linhas[] = '';

        foreach ($pedido->itens as $item) {
            $linha = '- '.$item->quantidade."\u{00d7} ".$item->nome_produto;
            $adicionais = 0.0;

            foreach ($item->complementos ?? [] as $c) {
                $ehAdicional = ($c['tipo'] ?? 'adicional') === 'adicional';
                if ($ehAdicional) {
                    $adicionais += (float) ($c['preco'] ?? 0);
                    $linha .= "\n    \u{2022} ".($c['nome'] ?? '').' (+'.preco_br($c['preco'] ?? 0).')';
                } else {
                    $linha .= "\n    \u{2022} ".str_replace(':nome', $c['nome'] ?? '', texto('carrinho', 'comp_sem', 'sem :nome'));
                }
            }

            $linhas[] = $linha.' = '.preco_br($item->subtotal());
        }

        $linhas[] = '';
        $linhas[] = texto('admin_pedidos', 'whatsapp.total', 'Total').': '.preco_br($pedido->total);

        if ($pedido->observacoes) {
            $linhas[] = texto('checkout', 'campo.observacoes', 'Observações').': '.$pedido->observacoes;
        }

        return implode("\n", $linhas);
    }
}
