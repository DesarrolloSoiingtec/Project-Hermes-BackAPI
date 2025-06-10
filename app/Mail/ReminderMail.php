<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Person;
use App\Models\Other\Specialty;
use Carbon\Carbon;
use App\Models\Siau\TrainingCourse;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $person;
    public $patient;
    public $agreementPatient;
    public $trainingCourse;
    public $appointmentDate;
    public $trainingLink;

    public function __construct($person, $patient, $agreementPatient, $trainingCourse, $appointmentDate, $trainingLink, $hour_appointment)
    {

        Log::info('ReminderMail constructor called');
        Log::info('Person: ' . $person->id);
        Log::info('Patient: ' . $patient->id);
        Log::info('Agreement Patient: ' . $agreementPatient->id);
        Log::info('Training Course: ' . $trainingCourse->all());
        Log::info('Appointment Date: ' . $appointmentDate);
        Log::info('Training Link: ' . $trainingLink);
        Log::info('Hour Appointment: ' . $hour_appointment);


        $appointmentDate = $trainingCourse->date_appointment;
        $medical_id = $trainingCourse->medical_id;
        $medical = Person::where('id', $medical_id)->first();

        $specialty_id = $trainingCourse->specialty_id;
        $specialty = Specialty::where('id', $specialty_id)->first();

        Carbon::setLocale('es');
        $dateFormatted = Carbon::parse($appointmentDate)->isoFormat('dddd DD [de] MMMM [de] YYYY');

        Carbon::setLocale('es');
        $hour = Carbon::createFromFormat('H:i', $hour_appointment)->format('h:i A');

        $course_id = $trainingCourse->training_course_id;
        $course = TrainingCourse::where('id', $course_id)->value('name');

        $this->person = $person;
        $this->patient = $patient;
        $this->agreementPatient = $agreementPatient;
        $this->trainingCourse = $trainingCourse;
        $this->trainingLink = "https://frontend-medyser.space/capacitacion-pedagogica";

        $this->medical = $medical;
        $this->specialty = $specialty;
        $this->appointmentDate = $dateFormatted;
        $this->hourAppointment = $hour;
        $this->course = $course;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('🕒 Capacitación asignada por inasistencia')
            ->markdown('emails.reminder')
            ->with([
                'firstName'    => $this->person->name,
                'lastName'     => $this->person->lastname,
                'medical'      => $this->medical->name,
                'specialty'    => $this->specialty->name,
                'date'         => $this->appointmentDate,
                'hour'         => $this->hourAppointment,
                'trainingLink' => $this->trainingLink,
                'course'       => $this->course,
            ]);
    }
}
