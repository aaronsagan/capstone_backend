<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PaymentMethodUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $action;
    public $paymentType;
    public $last4Digits;
    public $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $action,
        string $paymentType,
        ?string $last4Digits = null,
        ?Carbon $timestamp = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->paymentType = $paymentType;
        $this->last4Digits = $last4Digits;
        $this->timestamp = $timestamp ?? now();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $actionTitle = ucfirst($this->action);
        
        return new Envelope(
            subject: "Payment Method {$actionTitle} — CharityHub Confirmation",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.method-updated',
            with: [
                'userName' => $this->user->name,
                'action' => $this->action,
                'actionTitle' => ucfirst($this->action),
                'paymentType' => $this->paymentType,
                'last4Digits' => $this->last4Digits,
                'timestamp' => $this->timestamp->format('F d, Y h:i A'),
                'dashboardUrl' => config('app.frontend_url') . '/donor/billing',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
