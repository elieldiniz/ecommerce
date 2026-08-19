<?php

namespace Database\Seeders;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Popula painel/vendas/ com pedidos variados para conferência visual local.
 * Não faz parte do DatabaseSeeder padrão — rodar sob demanda:
 * vendor/bin/sail artisan db:seed --class=DemoVendasSeeder
 */
class DemoVendasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paid = OrderStatus::where('slug', 'paid')->firstOrFail();
        $awaitingPayment = OrderStatus::where('slug', 'awaiting_payment')->firstOrFail();
        $cancelled = OrderStatus::where('slug', 'cancelled')->firstOrFail();

        $sentToGfsis = OrderFulfillmentStatus::where('slug', 'sent_to_gfsis')->firstOrFail();
        $awaitingData = OrderFulfillmentStatus::where('slug', 'awaiting_data')->firstOrFail();
        $dataComplete = OrderFulfillmentStatus::where('slug', 'data_complete')->firstOrFail();
        $sendFailed = OrderFulfillmentStatus::where('slug', 'send_failed')->firstOrFail();

        $authorized = PaymentStatus::where('slug', 'authorized')->firstOrFail();
        $pending = PaymentStatus::where('slug', 'pending')->firstOrFail();

        $pix = PaymentMethod::where('slug', 'pix')->firstOrFail();
        $cartao = PaymentMethod::where('slug', 'cartao')->firstOrFail();
        $boleto = PaymentMethod::where('slug', 'boleto')->firstOrFail();

        // 5 pagos e emitidos
        for ($i = 0; $i < 5; $i++) {
            $this->createOrder($paid, $sentToGfsis, $authorized, fake()->randomElement([$pix, $cartao]), true, now()->subDays(fake()->numberBetween(1, 25)));
        }

        // 3 pagos, aguardando dados de emissão
        for ($i = 0; $i < 3; $i++) {
            $this->createOrder($paid, $awaitingData, $authorized, fake()->randomElement([$pix, $cartao, $boleto]), true, now()->subDays(fake()->numberBetween(1, 10)));
        }

        // 2 pagos, dados completos, ainda não enviados
        for ($i = 0; $i < 2; $i++) {
            $this->createOrder($paid, $dataComplete, $authorized, $cartao, true, now()->subDays(fake()->numberBetween(1, 5)));
        }

        // 2 pagos, falha no envio ao GFSIS
        for ($i = 0; $i < 2; $i++) {
            $this->createOrder($paid, $sendFailed, $authorized, $boleto, true, now()->subDays(fake()->numberBetween(1, 8)));
        }

        // 2 aguardando pagamento
        for ($i = 0; $i < 2; $i++) {
            $this->createOrder($awaitingPayment, $awaitingData, $pending, fake()->randomElement([$pix, $boleto]), false, now()->subHours(fake()->numberBetween(1, 48)));
        }

        // 1 cancelado
        $this->createOrder($cancelled, $awaitingData, $pending, $cartao, false, now()->subDays(fake()->numberBetween(1, 15)));
    }

    private function createOrder(
        OrderStatus $status,
        OrderFulfillmentStatus $fulfillmentStatus,
        PaymentStatus $paymentStatus,
        PaymentMethod $paymentMethod,
        bool $paid,
        CarbonInterface $createdAt,
    ): void {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'fulfillment_status_id' => $fulfillmentStatus->id,
            'payment_method_id' => $paymentMethod->id,
            'paid_at' => $paid ? $createdAt->copy()->addMinutes(3) : null,
            'created_at' => $createdAt,
        ]);

        $variant = ProductVariant::inRandomOrder()->first() ?? ProductVariant::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'unit_price' => $order->total,
            'list_price_snapshot' => $order->total,
        ]);

        if ($paid) {
            (new GenerateIssuanceAccessToken)->execute($order->fresh(['items', 'customer']));
        }

        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'status_id' => $paymentStatus->id,
            'gross_amount' => $order->total,
            'paid_at' => $paid ? $createdAt->copy()->addMinutes(3) : null,
        ]);
    }
}
