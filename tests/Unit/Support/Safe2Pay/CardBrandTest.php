<?php

namespace Tests\Unit\Support\Safe2Pay;

use App\Support\Safe2Pay\CardBrand;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CardBrandTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function codesData(): array
    {
        return [
            '1 Visa' => [1, 'Visa'],
            '2 Master Card' => [2, 'Mastercard'],
            '3 American Express' => [3, 'American Express'],
            '7 Elo' => [7, 'Elo'],
            '8 Aura' => [8, 'Aura'],
            '9 JCB' => [9, 'JCB'],
            '10 Diners Club' => [10, 'Diners Club'],
            '11 Discover' => [11, 'Discover'],
        ];
    }

    #[DataProvider('codesData')]
    public function test_each_code_maps_to_the_expected_label(int $code, string $expectedLabel): void
    {
        $brand = CardBrand::tryFrom($code);

        $this->assertNotNull($brand);
        $this->assertSame($expectedLabel, $brand->label());
    }

    public function test_unknown_code_returns_null_instead_of_throwing(): void
    {
        $this->assertNull(CardBrand::tryFrom(999));
    }
}
