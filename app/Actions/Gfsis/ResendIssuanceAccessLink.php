<?php

namespace App\Actions\Gfsis;

use App\Mail\IssuanceAccessLinkMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ResendIssuanceAccessLink
{
    public function __construct(private GenerateIssuanceAccessToken $generateIssuanceAccessToken) {}

    /**
     * Pedidos que chegam à fila `paid`+`awaiting_data` sem nunca ter passado
     * por `GenerateIssuanceAccessToken::execute()` (dado legado/inconsistente)
     * ainda não têm `issuance_data` — cria antes de regenerar, para não
     * quebrar `regenerate()->firstOrFail()`. O token é sempre regenerado
     * mesmo quando o e-mail falha (ex.: endereço do cliente inválido) — só o
     * envio é reportado como falho, para o chamador decidir o que exibir/
     * marcar, sem nunca deixar uma exceção de envio derrubar a requisição.
     */
    public function execute(Order $order): bool
    {
        $orderItem = $order->items->first();

        if ($orderItem->issuanceData === null) {
            $this->generateIssuanceAccessToken->execute($order);
        }

        $token = $this->generateIssuanceAccessToken->regenerate($orderItem);

        $url = route('pedido.emissao', ['id' => $order->id, 'token' => $token]);

        try {
            Mail::to($order->customer->email)->send(new IssuanceAccessLinkMail($order, $url));
        } catch (Throwable $exception) {
            Log::error('recuperacao.reenvio_link_falhou', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
