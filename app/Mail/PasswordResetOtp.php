<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $expiresIn
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your CMS password reset OTP')
            ->text('emails.password-reset-otp');
    }
}
