<?php

namespace App\Http\Controllers\Pedido;

use App\Http\Controllers\Controller;
use App\Models\IssuanceData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowEmissaoController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * `EnsureIssuanceAccessTokenIsValid` (RF-17) já validou o `?token=` e
     * anexou a `IssuanceData` correspondente ao request antes deste controller
     * rodar, fechando o IDOR da rota.
     */
    public function __invoke(Request $request, int|string $id): View
    {
        $holderType = $request->query('tipo') === 'pj' ? 'pj' : 'pf';

        /** @var IssuanceData $issuanceData */
        $issuanceData = $request->attributes->get('issuanceData');
        $issuanceData->loadMissing('orderItem.order.paymentMethod');

        return view('pages.pedido.emissao', [
            'id' => $id,
            'holderType' => $holderType,
            'issuanceData' => $issuanceData,
            'order' => $issuanceData->orderItem->order,
        ]);
    }
}
