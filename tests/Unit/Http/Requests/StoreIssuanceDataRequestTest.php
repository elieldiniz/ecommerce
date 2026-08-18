<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreIssuanceDataRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StoreIssuanceDataRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')
            ->post('_test/emissao/', function (StoreIssuanceDataRequest $request) {
                return response()->json(['ok' => true]);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function pfPayload(): array
    {
        return [
            'holder_name' => 'Maria Aparecida Souza',
            'document' => '12345678901',
            'email' => 'maria@example.com',
            'phone' => '11999998888',
            'postal_code' => '01310100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }

    public function test_pf_payload_without_document_fails_validation(): void
    {
        $payload = $this->pfPayload();
        unset($payload['document']);

        $response = $this->post('_test/emissao/', $payload);

        $response->assertSessionHasErrors('document');
    }

    public function test_pj_payload_without_responsible_document_fails_validation(): void
    {
        $payload = array_merge($this->pfPayload(), [
            'responsible_name' => 'João da Silva',
            'responsible_email' => 'joao@example.com',
            'responsible_phone' => '11988887777',
        ]);

        $response = $this->post('_test/emissao/?tipo=pj', $payload);

        $response->assertSessionHasErrors('responsible_document');
    }

    public function test_complete_pf_payload_without_responsible_fields_passes(): void
    {
        $response = $this->post('_test/emissao/', $this->pfPayload());

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }
}
