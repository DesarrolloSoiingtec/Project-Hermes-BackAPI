<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CongratulationsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;

    public function __construct(string $firstName, string $lastName, string $trainingLink)
    {
        $this->firstName   = $firstName;
        $this->lastName    = $lastName;
        $this->trainingLink = $trainingLink;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('🎉 Felicitaciones por completar tu capacitación')
            ->markdown('emails.congratulations', [
                'firstName'   => $this->firstName,
                'lastName'    => $this->lastName,
                'trainingLink' => $this->trainingLink,
            ]);
    }
}
