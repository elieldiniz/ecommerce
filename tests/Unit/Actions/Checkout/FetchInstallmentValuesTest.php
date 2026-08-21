<?php

namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\FetchInstallmentValues;
use App\Exceptions\Payments\Safe2PayInstallmentQueryFailedException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchInstallmentValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.safe2pay.installment_base_url' => 'https://api.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.is_sandbox' => true,
        ]);
    }

    public function test_a_successful_response_is_mapped_from_response_detail_installments(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => [
                'Installments' => [
                    ['Installments' => 1, 'InstallmentValue' => '180.00', 'TotalValue' => '180.00', 'AppliedTax' => 0],
                    ['Installments' => 2, 'InstallmentValue' => '90.00', 'TotalValue' => '180.00', 'AppliedTax' => 0],
                    ['Installments' => 3, 'InstallmentValue' => '61.66', 'TotalValue' => '184.98', 'AppliedTax' => 2.99],
                ],
            ],
        ], 200)]);

        $rows = (new FetchInstallmentValues)->execute('180.00');

        $this->assertSame([
            ['installments' => 1, 'installment_value' => '180.00', 'total_value' => '180.00', 'applied_tax' => '0'],
            ['installments' => 2, 'installment_value' => '90.00', 'total_value' => '180.00', 'applied_tax' => '0'],
            ['installments' => 3, 'installment_value' => '61.66', 'total_value' => '184.98', 'applied_tax' => '2.99'],
        ], $rows);
    }

    public function test_a_second_call_with_the_same_amount_is_served_from_cache_without_a_new_http_call(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '180.00', 'TotalValue' => '180.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);

        (new FetchInstallmentValues)->execute('180.00');
        (new FetchInstallmentValues)->execute('180.00');

        Http::assertSentCount(1);
    }

    public function test_a_different_amount_triggers_a_new_http_call(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '180.00', 'TotalValue' => '180.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);

        (new FetchInstallmentValues)->execute('180.00');
        (new FetchInstallmentValues)->execute('90.00');

        Http::assertSentCount(2);
    }

    public function test_has_error_true_throws_the_dedicated_exception_without_caching_the_failure(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([
            'HasError' => true,
            'ErrorCode' => '301',
            'Error' => 'Recurso não permitido em Sandbox.',
        ], 200)]);

        try {
            (new FetchInstallmentValues)->execute('180.00');
            $this->fail('Esperava Safe2PayInstallmentQueryFailedException.');
        } catch (Safe2PayInstallmentQueryFailedException) {
            // esperado
        }

        try {
            (new FetchInstallmentValues)->execute('180.00');
            $this->fail('Esperava Safe2PayInstallmentQueryFailedException.');
        } catch (Safe2PayInstallmentQueryFailedException) {
            // esperado
        }

        Http::assertSentCount(2);
    }

    public function test_a_response_without_installments_throws_the_dedicated_exception(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => [],
        ], 200)]);

        $this->expectException(Safe2PayInstallmentQueryFailedException::class);

        (new FetchInstallmentValues)->execute('180.00');
    }

    public function test_an_http_failure_throws_the_dedicated_exception(): void
    {
        Http::fake(['*/v2/creditCard/installmentValue*' => Http::response([], 500)]);

        $this->expectException(Safe2PayInstallmentQueryFailedException::class);

        (new FetchInstallmentValues)->execute('180.00');
    }
}
