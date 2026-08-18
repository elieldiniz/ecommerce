<?php

namespace App\Actions\Gfsis;

use App\Models\GfsisStatus;
use App\Models\OrderItemGfsis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * RF-12: mapeia `status` do payload do webhook GFSIS para o slug de
 * `gfsis_statuses` e aplica a transição em `order_item_gfsis` apenas quando
 * `dataAtualizacao` do payload for estritamente mais recente que
 * `status_synced_at` já gravado (não regressão por data). `dataValidade`,
 * quando presente, é gravado em `certificate_expires_at` sob a mesma
 * condição. O formato primário confirmado (Seção 12.4) é `d/m/Y` para
 * `dataAtualizacao` e `d/m/Y H:i` para `dataValidade`; `Carbon::parse()` é
 * usado apenas como fallback defensivo quando o formato estrito não
 * corresponde, sempre registrando um aviso de log nesse caso.
 */
class ApplyGfsisStatusTransition
{
    /**
     * @var array<string, string>
     */
    private const STATUS_SLUG_MAP = [
        'CRIADO' => 'enviado_gfsis',
        'ENVIADO' => 'enviado_gfsis',
        'APROVADO' => 'aprovado',
        'EMITIDO' => 'emitido',
        'RECUSADO' => 'falha_envio',
        'CANCELADO' => 'cancelado',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(OrderItemGfsis $orderItemGfsis, array $payload): void
    {
        $dataAtualizacao = $this->parseDate(
            'd/m/Y',
            (string) ($payload['dataAtualizacao'] ?? ''),
            'dataAtualizacao',
            resetTime: true,
        );

        if ($orderItemGfsis->status_synced_at !== null && ! $dataAtualizacao->greaterThan($orderItemGfsis->status_synced_at)) {
            return;
        }

        $updates = ['status_synced_at' => $dataAtualizacao];

        $slug = self::STATUS_SLUG_MAP[(string) ($payload['status'] ?? '')] ?? null;

        if ($slug !== null) {
            $updates['status_id'] = GfsisStatus::query()->where('slug', $slug)->value('id');
        }

        if (! empty($payload['dataValidade'])) {
            $updates['certificate_expires_at'] = $this->parseDate(
                'd/m/Y H:i',
                (string) $payload['dataValidade'],
                'dataValidade',
            );
        }

        $orderItemGfsis->update($updates);
    }

    private function parseDate(string $format, string $value, string $field, bool $resetTime = false): Carbon
    {
        try {
            $parsed = Carbon::createFromFormat($format, $value);
        } catch (\Throwable) {
            $parsed = false;
        }

        if ($parsed !== false && $parsed->format($format) === $value) {
            return $resetTime ? $parsed->startOfDay() : $parsed;
        }

        Log::warning("GFSIS: campo {$field} ('{$value}') não corresponde ao formato primário '{$format}'; usando fallback Carbon::parse().");

        return Carbon::parse($value);
    }
}
