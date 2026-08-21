<?php

namespace Tests\Feature\Pages\Auth;

use App\Models\Customer;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('customer.password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();

        Livewire::test('pages::auth.customer.forgot-password')
            ->set('email', $customer->email)
            ->call('sendResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
    }

    public function test_reset_link_request_with_unknown_email_shows_generic_error(): void
    {
        Notification::fake();

        Livewire::test('pages::auth.customer.forgot-password')
            ->set('email', 'unknown@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();

        Livewire::test('pages::auth.customer.forgot-password')
            ->set('email', $customer->email)
            ->call('sendResetLink');

        Notification::assertSentTo($customer, CustomerResetPasswordNotification::class, function ($notification) {
            $response = $this->get(route('customer.password.reset', $notification->token));

            $response->assertOk();

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create(['password' => 'old-password']);

        Livewire::test('pages::auth.customer.forgot-password')
            ->set('email', $customer->email)
            ->call('sendResetLink');

        Notification::assertSentTo($customer, CustomerResetPasswordNotification::class, function ($notification) use ($customer) {
            Livewire::test('pages::auth.customer.reset-password', ['token' => $notification->token])
                ->set('email', $customer->email)
                ->set('password', 'new-password123')
                ->set('password_confirmation', 'new-password123')
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect(route('customer.login'));

            return true;
        });

        $this->assertTrue(Hash::check('new-password123', $customer->refresh()->password));
    }

    public function test_a_cart_intent_from_forgot_password_survives_the_email_round_trip_and_chains_into_the_login_redirect(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create(['password' => 'old-password']);

        Livewire::test('pages::auth.customer.forgot-password')
            ->set('from', 'carrinho')
            ->set('email', $customer->email)
            ->call('sendResetLink');

        Notification::assertSentTo($customer, CustomerResetPasswordNotification::class, function ($notification) use ($customer) {
            Livewire::test('pages::auth.customer.reset-password', ['token' => $notification->token])
                ->set('email', $customer->email)
                ->set('password', 'new-password123')
                ->set('password_confirmation', 'new-password123')
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect(route('customer.login', ['from' => 'carrinho']));

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $customer = Customer::factory()->create(['password' => 'old-password']);

        Livewire::test('pages::auth.customer.reset-password', ['token' => 'invalid-token'])
            ->set('email', $customer->email)
            ->set('password', 'new-password123')
            ->set('password_confirmation', 'new-password123')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $customer->refresh()->password));
    }
}
