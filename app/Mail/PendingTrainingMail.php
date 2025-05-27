<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingTrainingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $person;
    public $pendingCoursesCount;
    public $trainingLink;

    public function __construct($person, $pendingCoursesCount, $trainingLink)
    {
        $this->person = $person;
        $this->pendingCoursesCount = $pendingCoursesCount;
        $this->trainingLink = $trainingLink;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('📚 Tienes capacitaciones pendientes')
            ->markdown('emails.pending-training')
            ->with([
                'firstName'           => $this->person->name,
                'lastName'            => $this->person->lastname,
                'pendingCoursesCount' => $this->pendingCoursesCount,
                'trainingLink'        => $this->trainingLink,
            ]);
    }
}
