<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\Efi;
use App\Services\MercadoPago;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PedidoController extends Controller
{
    public function confirmacao(string $codigo): View
    {
        $pedido = Pedido::with('itens')->where('codigo', $codigo)->firstOrFail();

        $copiaECola = null;

        // Mercado Pago: segunda camada de conferência (além do webhook)
        if ($pedido->forma_pagamento === 'cartao_mp' && $pedido->pagamento_status !== 'pago') {
            $resultado = app(MercadoPago::class)->consultarPorReferencia($pedido->codigo);

            if ($resultado && $resultado['status'] === 'approved') {
                $pedido->update(['pagamento_status' => 'pago', 'pagamento_id' => $resultado['pagamento_id']]);
                $pedido->refresh();
            }
        }

        // Efí: garante a existência da cobrança e puxa o copia e cola
        if ($pedido->forma_pagamento === 'pix_efi' && $pedido->pagamento_status !== 'pago') {
            try {
                $cobranca = app(Efi::class)->criarCobranca($pedido);
                $pedido->update(['pagamento_status' => 'pendente', 'pagamento_id' => $cobranca['txid']]);

                if ($cobranca['status'] === 'CONCLUIDA') {
                    $pedido->update(['pagamento_status' => 'pago']);
                    $pedido->refresh();
                } else {
                    $copiaECola = $cobranca['copia_e_cola'];
                }
            } catch (\RuntimeException) {
                // Sem contato com a Efí agora: a tela mostra o pendente e
                // o cliente pode recarregar mais tarde.
            }
        }

        return view('pedidos.confirmacao', [
            'pedido' => $pedido,
            'chavePix' => config_loja('chave_pix'),
            'copiaECola' => $copiaECola,
        ]);
    }

    /**
     * Pagar de novo (Mercado Pago): recria a preferência de um pedido
     * pendente sem tocar no estoque nem recriar o pedido.
     */
    public function pagar(string $codigo): RedirectResponse
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        if ($pedido->forma_pagamento !== 'cartao_mp' || $pedido->pagamento_status === 'pago') {
            return redirect()->route('pedido.confirmacao', $pedido->codigo);
        }

        try {
            $preferencia = app(MercadoPago::class)->criarPreferencia($pedido);
            $pedido->update(['pagamento_status' => 'pendente', 'pagamento_id' => $preferencia['preferencia_id']]);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('pedido.confirmacao', $pedido->codigo)
                ->with('erro_pagamento', $e->getMessage());
        }

        return redirect()->away($preferencia['url']);
    }
}
