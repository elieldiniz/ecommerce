<?php

namespace App\Http\Middleware;

use App\Models\IssuanceData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIssuanceAccessTokenIsValid
{
    /**
     * Resolve `IssuanceData` pelo `access_token` da querystring (`?token=`) e
     * pelo `order_item->order_id` do pedido da rota (`{id}`); aborta 403 quando
     * ausente, sem correspondência, ou expirado (RF-17 — fecha o IDOR da rota
     * de emissão). Anexa a instância resolvida em `$request->attributes` para
     * reuso pelos controllers consumidores, evitando buscar duas vezes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');

        if (! $token) {
            abort(403);
        }

        $issuanceData = IssuanceData::query()
            ->where('access_token', $token)
            ->whereHas('orderItem', fn ($query) => $query->where('order_id', (int) $request->route('id')))
            ->first();

        if (! $issuanceData) {
            abort(403);
        }

        if ($issuanceData->access_token_expires_at->isPast()) {
            abort(403);
        }

        $request->attributes->set('issuanceData', $issuanceData);

        return $next($request);
    }
}
