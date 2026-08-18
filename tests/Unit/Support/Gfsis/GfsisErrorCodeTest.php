<?php

namespace Tests\Unit\Support\Gfsis;

use App\Support\Gfsis\GfsisErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GfsisErrorCodeTest extends TestCase
{
    public function test_code_002_is_the_only_duplicate_success(): void
    {
        $this->assertTrue(GfsisErrorCode::PedidoDuplicado->isDuplicateSuccess());
    }

    /**
     * @return array<string, array{0: GfsisErrorCode}>
     */
    public static function nonDuplicateCodes(): array
    {
        return [
            '001' => [GfsisErrorCode::CampoFaltante],
            '003' => [GfsisErrorCode::CepInvalido],
            '005' => [GfsisErrorCode::CertificadoInvalido],
            '006' => [GfsisErrorCode::DocumentoInvalido],
            '999' => [GfsisErrorCode::ErroInesperado],
        ];
    }

    #[DataProvider('nonDuplicateCodes')]
    public function test_other_codes_are_not_duplicate_success(GfsisErrorCode $code): void
    {
        $this->assertFalse($code->isDuplicateSuccess());
    }

    public function test_message_for_code_005_references_certificado(): void
    {
        $this->assertStringContainsStringIgnoringCase('certificado', GfsisErrorCode::CertificadoInvalido->message());
    }

    public function test_from_code_returns_null_for_unknown_code(): void
    {
        $this->assertNull(GfsisErrorCode::fromCode('123'));
    }

    public function test_from_code_returns_null_for_null_code(): void
    {
        $this->assertNull(GfsisErrorCode::fromCode(null));
    }

    public function test_from_code_resolves_known_codes(): void
    {
        $this->assertSame(GfsisErrorCode::PedidoDuplicado, GfsisErrorCode::fromCode('002'));
    }
}
