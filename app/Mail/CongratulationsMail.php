<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Auth\Person;
use Illuminate\Support\Facades\Log;
use App\Models\Siau\patient_training_course;
use App\Models\Siau\TrainingCourse;
use Carbon\Carbon;

class CongratulationsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $person;
    public $trainingLink;
    public $firstName;
    public $secondName;
    public $lastName;
    public $documentNumber;
    public $courseName;
    public $completionDate;
    public $startDate;

    public function __construct($person, string $trainingLink)
    {
        Log::info("Iniciando CongratulationsMail para usuario ID: " . $person->id);

        // Almacenar los datos de la persona
        $this->person = $person;
        $this->firstName = $person->name;
        $this->secondName = $person->second_name;
        $this->lastName = $person->lastname;
        $this->documentNumber = $person->document_number;
        $this->trainingLink = $trainingLink;

        // Primero buscar el curso más reciente con estado 3
        $courseWithStateThree = patient_training_course::where('patient_person_id', $person->id)
            ->where('state', 3)
            ->orderBy('updated_at', 'desc')
            ->first();

        $selectedCourse = null;

        if ($courseWithStateThree) {
            // Si encontramos un curso con estado 3, lo usamos
            $selectedCourse = $courseWithStateThree;
            Log::info("Usando curso con estado 3");
        } else {
            // Si no hay curso con estado 3, buscamos el segundo más reciente de cualquier estado
            $allCourses = patient_training_course::where('patient_person_id', $person->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            if (count($allCourses) >= 2) {
                $selectedCourse = $allCourses[1]; // El segundo más reciente
                Log::info("No hay cursos con estado 3, usando el segundo más reciente");
            } elseif (count($allCourses) == 1) {
                $selectedCourse = $allCourses[0]; // Solo hay uno
                Log::info("No hay cursos con estado 3 y solo hay un registro, usando ese");
            }
        }

        if ($selectedCourse) {
            // Obtener el nombre del curso
            $trainingCourse = TrainingCourse::find($selectedCourse->training_course_id);
            $this->courseName = $trainingCourse ? $trainingCourse->name : 'Curso no especificado';

            // Formatear fechas
            Carbon::setLocale('es');
            $this->completionDate = Carbon::parse($selectedCourse->updated_at)->isoFormat('dddd DD [de] MMMM [de] YYYY [a las] HH:mm');
            $this->startDate = Carbon::parse($selectedCourse->created_at)->isoFormat('dddd DD [de] MMMM [de] YYYY [a las] HH:mm');

            Log::info("Curso seleccionado: " . $this->courseName);
            Log::info("Fecha de finalización: " . $this->completionDate);
            Log::info("Fecha de inicio: " . $this->startDate);
        } else {
            Log::warning("No se encontraron cursos para el usuario ID: " . $person->id);
            $this->courseName = 'Curso no encontrado';
            $this->completionDate = 'Fecha no disponible';
            $this->startDate = 'Fecha no disponible';
        }
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('✅ Certificado de Finalización - Capacitación Pedagógica Completada')
            ->markdown('emails.congratulations', [
                'firstName'      => $this->firstName,
                'secondName'     => $this->secondName,
                'lastName'       => $this->lastName,
                'documentNumber' => $this->documentNumber,
                'courseName'     => $this->courseName,
                'completionDate' => $this->completionDate,
                'startDate'      => $this->startDate,
                'trainingLink'   => $this->trainingLink,
            ]);
    }
}
