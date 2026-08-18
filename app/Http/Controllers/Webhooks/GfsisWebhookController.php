<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GfsisWebhookController extends Controller
{
    /**
     * Stub mínimo para manter `routes/web.php` bootável (T15). O journaling
     * incondicional e a idempotência por `event_hash` (RF-11, RNF-04) são
     * implementados por uma task posterior desta fase (T16).
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['received' => true]);
    }
}
