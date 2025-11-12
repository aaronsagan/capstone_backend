<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TooManyLoginAttempts extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $lockDuration;
    public $attemptCount;

    public function __construct(User $user, int $lockDuration = 10, int $attemptCount = 5)
    {
        $this->user = $user;
        $this->lockDuration = $lockDuration;
        $this->attemptCount = $attemptCount;
    }

    public function build()
    {
        return $this->subject('🔒 Security Alert: Multiple Failed Login Attempts')
                    ->view('emails.security.too-many-login-attempts');
    }
}
