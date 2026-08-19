<?php

namespace App\Actions\Gfsis;

use App\Mail\IssuanceAccessLinkMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class ResendIssuanceAccessLink
{
    public function __construct(private GenerateIssuanceAccessToken $generateIssuanceAccessToken) {}

    public function execute(Order $order): void
    {
        $orderItem = $order->items->first();

        $token = $this->generateIssuanceAccessToken->regenerate($orderItem);

        $url = route('pedido.emissao', ['id' => $order->id, 'token' => $token]);

        Mail::to($order->customer->email)->send(new IssuanceAccessLinkMail($order, $url));
    }
}
