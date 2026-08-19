<?php

namespace Tests\Unit\Actions\Gfsis;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Actions\Gfsis\ResendIssuanceAccessLink;
use App\Mail\IssuanceAccessLinkMail;
use App\Models\Customer;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendIssuanceAccessLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_executing_regenerates_token_and_sends_email_to_customer(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'cliente@exemplo.com.br']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        (new GenerateIssuanceAccessToken)->execute($order);
        $tokenAntigo = IssuanceData::query()->firstOrFail()->access_token;

        $sucesso = app(ResendIssuanceAccessLink::class)->execute($order->fresh(['items', 'customer']));

        $tokenNovo = IssuanceData::query()->firstOrFail()->access_token;
        $this->assertTrue($sucesso);
        $this->assertNotSame($tokenAntigo, $tokenNovo);

        Mail::assertSent(IssuanceAccessLinkMail::class, fn ($mail) => $mail->hasTo('cliente@exemplo.com.br')
            && str_contains($mail->url, $tokenNovo));
    }

    public function test_a_malformed_customer_email_does_not_throw_still_regenerates_the_token_and_reports_failure(): void
    {
        Log::spy();

        $customer = Customer::factory()->create(['email' => 'eliel diniz1@outl.com']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        (new GenerateIssuanceAccessToken)->execute($order);
        $tokenAntigo = IssuanceData::query()->firstOrFail()->access_token;

        $sucesso = app(ResendIssuanceAccessLink::class)->execute($order->fresh(['items', 'customer']));

        $tokenNovo = IssuanceData::query()->firstOrFail()->access_token;
        $this->assertFalse($sucesso);
        $this->assertNotSame($tokenAntigo, $tokenNovo);

        Log::shouldHaveReceived('error')->once();
    }
}
