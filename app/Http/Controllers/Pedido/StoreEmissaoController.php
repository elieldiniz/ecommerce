<?php

namespace App\Http\Controllers\Pedido;

use App\Actions\Gfsis\MarkIssuanceDataComplete;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssuanceDataRequest;
use App\Models\IssuanceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreEmissaoController extends Controller
{
    /**
     * `EnsureIssuanceAccessTokenIsValid` (RF-17) já validou o `?token=` e
     * anexou a `IssuanceData` correspondente ao request antes deste
     * controller rodar; `StoreIssuanceDataRequest` (RF-02) já validou os
     * campos condicionais PF/PJ antes deste método executar.
     */
    public function __invoke(StoreIssuanceDataRequest $request, int|string $id): RedirectResponse
    {
        /** @var IssuanceData $issuanceData */
        $issuanceData = $request->attributes->get('issuanceData');

        DB::transaction(function () use ($request, $issuanceData): void {
            $issuanceData->update($request->validated());

            (new MarkIssuanceDataComplete)->execute($issuanceData->fresh());
        });

        return redirect()
            ->route('pedido.emissao', [
                'id' => $id,
                'token' => $issuanceData->access_token,
                'tipo' => $request->query('tipo'),
            ])
            ->with('status', 'Dados de emissão enviados com sucesso.');
    }
}
