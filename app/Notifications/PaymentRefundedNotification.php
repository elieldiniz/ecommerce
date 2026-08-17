<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class PaymentRefundedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Payment $payment)
    {
        //
    }

    /**
     * Send this notification to every user with `roles.slug = 'finance'` (RF-10).
     */
    public static function sendToFinanceTeam(Payment $payment): void
    {
        $financeUsers = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'finance'))
            ->get();

        NotificationFacade::send($financeUsers, new self($payment));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->payment->order;

        return (new MailMessage)
            ->subject('Pagamento estornado — pedido '.$order->number)
            ->line('O pagamento do pedido '.$order->number.' foi estornado.')
            ->line('Valor do pagamento: R$ '.number_format((float) $this->payment->gross_amount, 2, ',', '.'));
    }
}
