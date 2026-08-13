<?php

namespace App\Http\Controllers\Pedido;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowEmissaoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, int|string $id): View
    {
        $holderType = $request->query('tipo') === 'pj' ? 'pj' : 'pf';

        return view('pages.pedido.emissao', [
            'id' => $id,
            'holderType' => $holderType,
        ]);
    }
}
