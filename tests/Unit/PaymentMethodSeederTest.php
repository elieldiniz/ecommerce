<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentMethodSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_exactly_three_active_payment_methods(): void
    {
        $this->seed(PaymentMethodSeeder::class);

        $this->assertSame(3, PaymentMethod::query()->where('is_active', true)->count());
        $this->assertSame(
            ['boleto', 'cartao', 'pix'],
            PaymentMethod::query()->pluck('slug')->sort()->values()->all()
        );
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function paymentMethodsData(): array
    {
        return [
            'pix' => ['pix', 1, '0.00'],
            'cartao' => ['cartao', 12, '0.00'],
            'boleto' => ['boleto', 1, '0.00'],
        ];
    }

    #[DataProvider('paymentMethodsData')]
    public function test_each_payment_method_has_max_installments_and_discount_filled(
        string $slug,
        int $expectedMaxInstallments,
        string $expectedDiscount
    ): void {
        $this->seed(PaymentMethodSeeder::class);

        $method = PaymentMethod::query()->where('slug', $slug)->first();

        $this->assertNotNull($method);
        $this->assertSame($expectedMaxInstallments, $method->max_installments);
        $this->assertSame($expectedDiscount, $method->discount_percentage);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_rows(): void
    {
        $this->seed(PaymentMethodSeeder::class);
        $this->seed(PaymentMethodSeeder::class);

        $this->assertSame(3, PaymentMethod::query()->count());
    }
}
