<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Votre code de vérification SoftBridge')
            ->html('<h1>Code de vérification</h1><p>Votre code OTP est : <strong>' . $this->code . '</strong></p><p>Ce code expire dans 2 minutes.</p>');
    }
}