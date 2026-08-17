<?php

namespace Tests\Unit\Support\Safe2Pay;

use App\Support\Safe2Pay\TransactionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionStatusTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function codesData(): array
    {
        return [
            '1 Pendente' => [1, 'pending'],
            '2 Processamento' => [2, 'under_review'],
            '3 Autorizado' => [3, 'authorized'],
            '5 Em disputa' => [5, 'under_review'],
            '6 Estornado' => [6, 'reversed'],
            '7 Baixado' => [7, 'expired'],
            '8 Recusado' => [8, 'denied'],
            '11 Liberado' => [11, 'pending'],
            '12 Em cancelamento' => [12, 'pending'],
            '13 Chargeback' => [13, 'reversed'],
            '14 Pré-Autorizado' => [14, 'under_review'],
            '15 Devolução de Contestação' => [15, 'authorized'],
            '19 Em devolução' => [19, 'under_review'],
        ];
    }

    #[DataProvider('codesData')]
    public function test_each_code_maps_to_the_expected_internal_status_slug(int $code, string $expectedSlug): void
    {
        $status = TransactionStatus::fromCode($code);

        $this->assertSame($expectedSlug, $status->toInternalStatusSlug());
    }

    public function test_baixado_maps_specifically_to_expired_and_not_to_another_slug(): void
    {
        $status = TransactionStatus::fromCode(7);

        $this->assertSame('expired', $status->toInternalStatusSlug());
        $this->assertNotSame('pending', $status->toInternalStatusSlug());
    }

    public function test_unknown_code_throws_value_error(): void
    {
        $this->expectException(\ValueError::class);

        TransactionStatus::fromCode(99);
    }
}
