<?php

namespace Tests\Feature\CrossCutting;

use App\Jobs\ProcessGfsisWebhookJob;
use App\Models\GfsisEvent;
use App\Support\Gfsis\GfsisClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Testes cross-cutting de RNF-01 (credenciais GFSIS nunca em asset publicado),
 * RNF-02 (base_url do GFSIS sempre via config, nunca literal fixo) e RNF-04
 * (resposta do webhook nunca aguarda a interpretação assíncrona) — T24.
 */
class GfsisSecurityTest extends TestCase
{
    use RefreshDatabase;

    // RNF-01

    public function test_no_frontend_source_asset_references_the_gfsis_password(): void
    {
        $files = [
            ...File::allFiles(resource_path('js')),
            ...File::allFiles(resource_path('css')),
        ];

        foreach ($files as $file) {
            $content = File::get($file->getPathname());

            $this->assertStringNotContainsString('GFSIS_SENHA', $content, "O arquivo {$file->getPathname()} não deve referenciar GFSIS_SENHA (RNF-01).");
            $this->assertStringNotContainsString('services.gfsis.senha', $content, "O arquivo {$file->getPathname()} não deve referenciar services.gfsis.senha (RNF-01).");
        }
    }

    public function test_no_published_build_asset_contains_the_gfsis_password_string(): void
    {
        $buildPath = public_path('build');

        if (! File::isDirectory($buildPath)) {
            $this->markTestSkipped('public/build não existe — assets não foram publicados neste ambiente.');
        }

        config(['services.gfsis.senha' => 'senha-secreta-gfsis-teste']);
        $senha = (string) config('services.gfsis.senha');

        foreach (File::allFiles($buildPath) as $file) {
            $content = File::get($file->getPathname());

            $this->assertStringNotContainsString($senha, $content, "O asset publicado {$file->getPathname()} não deve conter a senha GFSIS (RNF-01).");
            $this->assertStringNotContainsString('GFSIS_SENHA', $content, "O asset publicado {$file->getPathname()} não deve conter GFSIS_SENHA (RNF-01).");
        }
    }

    // RNF-02

    public function test_gfsis_client_never_hardcodes_the_base_url_outside_the_configuration_point(): void
    {
        $source = File::get(app_path('Support/Gfsis/GfsisClient.php'));

        $this->assertStringContainsString("config('services.gfsis.base_url')", $source, 'base_url deve ser lido de config() (RNF-02).');

        $sourceWithoutConfigurationPoint = str_replace(
            "config('services.gfsis.base_url')",
            '',
            $source
        );

        $this->assertDoesNotMatchRegularExpression(
            '/https?:\/\//',
            $sourceWithoutConfigurationPoint,
            'GfsisClient não pode conter nenhuma URL absoluta fora do ponto de configuração base_url (RNF-02).'
        );
    }

    public function test_auth_and_criar_pedido_venda_send_requests_to_the_configured_base_url_not_a_literal(): void
    {
        config([
            'services.gfsis.login' => 'login-teste',
            'services.gfsis.senha' => 'senha-teste',
        ]);

        config(['services.gfsis.base_url' => 'https://sandbox.gfsis.example.com']);
        Http::fake(['*' => Http::response(['acessToken' => 'token-sandbox', 'expirationDate' => now()->addHour()->toDateTimeString()], 200)]);
        (new GfsisClient)->auth();
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://sandbox.gfsis.example.com'));

        Cache::forget('gfsis.access_token');
        Http::fake(['*' => Http::response(['acessToken' => 'token-producao', 'expirationDate' => now()->addHour()->toDateTimeString()], 200)]);
        config(['services.gfsis.base_url' => 'https://producao.gfsis.example.com']);
        (new GfsisClient)->auth();
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://producao.gfsis.example.com'));
    }

    // RNF-04

    public function test_gfsis_webhook_controller_responds_200_without_waiting_for_bus_job_processing(): void
    {
        Bus::fake();

        $response = $this->postJson('/webhooks/gfsis', [
            'identificador' => 102930,
            'dataCriacao' => '24/08/2026',
            'dataAtualizacao' => '24/08/2026',
            'status' => 'APROVADO',
            'dataValidade' => '24/08/2027 09:03',
        ]);

        $response->assertOk();
        $response->assertJson(['received' => true]);

        Bus::assertDispatched(ProcessGfsisWebhookJob::class);

        $gfsisEvent = GfsisEvent::query()->first();
        $this->assertNotNull($gfsisEvent);
        $this->assertNull($gfsisEvent->processed_at, 'processed_at não deve ser gravado pela requisição do webhook — a interpretação é assíncrona (RNF-04).');
    }

    public function test_process_gfsis_webhook_job_is_dispatched_to_the_queue_never_executed_synchronously_inline(): void
    {
        $reflection = new \ReflectionClass(ProcessGfsisWebhookJob::class);

        $this->assertTrue(
            $reflection->implementsInterface(ShouldQueue::class),
            'ProcessGfsisWebhookJob deve implementar ShouldQueue para nunca ser processado de forma síncrona dentro da requisição do webhook (RNF-04).'
        );
    }
}
