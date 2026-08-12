<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Só o Pix é seedado. Cartão e boleto ficam como candidatos futuros — preço e
     * desconto por forma de pagamento são pendência de dado do negócio (ver
     * database-schema.md → Open Questions).
     */
    public function run(): void
    {
        PaymentMethod::query()->updateOrCreate(
            ['slug' => 'pix'],
            [
                'name' => 'Pix',
                'slug' => 'pix',
                'discount_percentage' => 0,
                'max_installments' => 1,
                'is_active' => true,
                'position' => 0,
            ]
        );
    }
}
