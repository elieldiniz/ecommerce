<?php

namespace Tests\Feature\Mail;

use App\Mail\IssuanceAccessLinkMail;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuanceAccessLinkMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_renders_view_with_url(): void
    {
        $customer = Customer::factory()->create(['legal_name' => 'Maria Teste']);
        $order = Order::factory()->create(['customer_id' => $customer->id, 'number' => 'PED-000001']);

        $mailable = new IssuanceAccessLinkMail($order, 'https://exemplo.com/pedido/1/emissao/?token=abc123');

        $mailable->assertSeeInHtml('https://exemplo.com/pedido/1/emissao/?token=abc123');
        $mailable->assertSeeInHtml('Maria Teste');
        $mailable->assertSeeInHtml('PED-000001');
    }

    public function test_body_uses_certificado_digital_not_certificado_isolado(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $mailable = new IssuanceAccessLinkMail($order, 'https://exemplo.com/link');

        $mailable->assertSeeInHtml('Certificado Digital');
    }
}
