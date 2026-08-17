<?php

namespace Tests\Unit\Support\Safe2Pay;

use App\Support\Safe2Pay\PaymentMethodCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentMethodCodeTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function slugsData(): array
    {
        return [
            'pix' => ['pix', '6'],
            'cartao' => ['cartao', '2'],
            'boleto' => ['boleto', '1'],
        ];
    }

    #[DataProvider('slugsData')]
    public function test_each_slug_maps_to_the_expected_payment_method_code(string $slug, string $expectedCode): void
    {
        $this->assertSame($expectedCode, PaymentMethodCode::fromSlug($slug));
    }

    public function test_unknown_slug_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PaymentMethodCode::fromSlug('desconhecido');
    }
}
