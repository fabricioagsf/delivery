<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\Efi;
use App\Services\MercadoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhooks dos gateways de pagamento (sem sessão/CSRF — chamados pelos
 * provedores). Sempre respondem 200: falha temporária do provedor não
 * deve gerar retentativas infinitas; a tela do pedido também confere
 * o status como segunda camada.
 */
class WebhookController extends Controller
{
    public function mercadopago(Request $request): JsonResponse
    {
        $id = (string) ($request->input('data.id') ?? $request->input('id') ?? '');

        if ($id !== '' && ctype_digit($id)) {
            $pagamento = app(MercadoPago::class)->buscarPagamento($id);

            if ($pagamento && $pagamento['external_reference'] !== '') {
                $pedido = Pedido::where('codigo', $pagamento['external_reference'])->first();

                if ($pedido) {
                    match ($pagamento['status']) {
                        'approved' => $pedido->update([
                            'pagamento_status' => 'pago',
                            'pagamento_id' => $pagamento['pagamento_id'],
                        ]),
                        'cancelled', 'rejected' => $pedido->update(['pagamento_status' => 'pendente']),
                        default => null,
                    };
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    public function efi(Request $request): JsonResponse
    {
        foreach ((array) $request->input('pix', []) as $evento) {
            $txid = (string) ($evento['txid'] ?? '');

            if ($txid === '') {
                continue;
            }

            $pedido = Pedido::where('pagamento_id', $txid)->first();

            if (! $pedido || $pedido->pagamento_status === 'pago') {
                continue;
            }

            $cobranca = app(Efi::class)->consultarCobranca($txid);

            if (($cobranca['status'] ?? '') === 'CONCLUIDA') {
                $pedido->update(['pagamento_status' => 'pago']);
            }
        }

        return response()->json(['ok' => true]);
    }
}
