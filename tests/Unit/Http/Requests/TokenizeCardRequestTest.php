<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TokenizeCardRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TokenizeCardRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')
            ->post('_test/tokenizar-cartao/', function (TokenizeCardRequest $request) {
                return response()->json(['ok' => true]);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'holder' => 'Maria Aparecida Souza',
            'cardNumber' => '5105105105105100',
            'expirationDate' => '12/2030',
            'securityCode' => '123',
        ];
    }

    public function test_missing_holder_card_number_expiration_date_or_security_code_fails_validation(): void
    {
        foreach (['holder', 'cardNumber', 'expirationDate', 'securityCode'] as $field) {
            $payload = $this->payload();
            unset($payload[$field]);

            $response = $this->post('_test/tokenizar-cartao/', $payload);

            $response->assertSessionHasErrors($field);
        }
    }

    public function test_a_valid_payload_passes_validation(): void
    {
        $response = $this->post('_test/tokenizar-cartao/', $this->payload());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_an_expiration_date_not_in_mm_slash_yy_format_fails_validation(): void
    {
        $payload = array_merge($this->payload(), ['expirationDate' => '13/2026']);

        $response = $this->post('_test/tokenizar-cartao/', $payload);

        $response->assertSessionHasErrors('expirationDate');
    }

    public function test_a_security_code_with_letters_or_wrong_length_fails_validation(): void
    {
        $withLetters = array_merge($this->payload(), ['securityCode' => 'abc']);
        $this->post('_test/tokenizar-cartao/', $withLetters)->assertSessionHasErrors('securityCode');

        $wrongLength = array_merge($this->payload(), ['securityCode' => '12']);
        $this->post('_test/tokenizar-cartao/', $wrongLength)->assertSessionHasErrors('securityCode');
    }
}
