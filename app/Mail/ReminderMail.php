<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $person;
    public $appointmentDate;
    public $trainingLink;

    public function __construct($person, $appointmentDate, $trainingLink)
    {
        $this->person         = $person;
        $this->appointmentDate = $appointmentDate;
        $this->trainingLink   = $trainingLink;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('🕒 Recordatorio: Capacitación pendiente')
            ->markdown('emails.reminder')
            ->with([
                'firstName'    => $this->person->name,
                'lastName'     => $this->person->lastname,
                'date'         => $this->appointmentDate,
                'trainingLink' => $this->trainingLink,
            ]);
    }
}