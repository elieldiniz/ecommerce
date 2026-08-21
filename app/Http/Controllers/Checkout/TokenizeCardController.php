<?php

namespace App\Http\Controllers\Checkout;

use App\Exceptions\Payments\Safe2PayTokenizationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TokenizeCardRequest;
use App\Support\Safe2Pay\CardBrand;
use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class TokenizeCardController extends Controller
{
    /**
     * Troca os dados brutos do cartão por um `Token` do Cofre de Chaves da Safe2Pay
     * (RF-01). Nunca loga nem persiste o payload bruto de requisição/resposta (RF-03) —
     * o corpo bruto só é carregado dentro de `Safe2PayTokenizationFailedException`, nunca
     * exposto ao cliente como está.
     */
    public function __invoke(TokenizeCardRequest $request, Safe2PayClient $client): JsonResponse
    {
        $payload = [
            'Holder' => $request->validated('holder'),
            'CardNumber' => $request->validated('cardNumber'),
            'ExpirationDate' => $request->validated('expirationDate'),
            'SecurityCode' => $request->validated('securityCode'),
        ];

        try {
            $response = $client->tokenize($payload);
            $response->throw();
        } catch (RequestException $e) {
            throw new Safe2PayTokenizationFailedException(['error' => $e->getMessage()]);
        }

        if ($response->json('HasError') === true) {
            throw new Safe2PayTokenizationFailedException((array) $response->json());
        }

        $brandCode = (int) $response->json('ResponseDetail.Brand');
        $cardNumber = (string) $response->json('ResponseDetail.CardNumber');

        return response()->json([
            'token' => (string) $response->json('ResponseDetail.Token'),
            'brand' => CardBrand::tryFrom($brandCode)?->label() ?? (string) $brandCode,
            'last4' => substr($cardNumber, -4),
        ]);
    }
}
