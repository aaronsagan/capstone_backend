<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $userEmail;
    public $code;
    public $token;
    public $verifyUrl;
    public $expiresIn;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->userName = $data['name'];
        $this->userEmail = $data['email'];
        $this->code = $data['code'];
        $this->token = $data['token'];
        $this->expiresIn = $data['expires_in'] ?? 15; // minutes
        $this->verifyUrl = config('app.frontend_url') . "/auth/verify-email?token={$this->token}&email={$this->userEmail}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your CharityHub Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'userName' => $this->userName,
                'userEmail' => $this->userEmail,
                'code' => $this->code,
                'verifyUrl' => $this->verifyUrl,
                'expiresIn' => $this->expiresIn,
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
