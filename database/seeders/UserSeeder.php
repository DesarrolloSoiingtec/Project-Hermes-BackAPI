<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Auth\Person;
use App\Models\Auth\Patient;
use App\Models\Siau\AgreementsPatients;
use App\Models\Siau\patient_training_course;
use App\Models\Siau\TrainingCourse;
use App\Models\APB\Agreement;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear o actualizar un convenio (agreement) de ejemplo
        $agreement = Agreement::updateOrCreate(
            ['number' => 'AG-001'],
            [
                'name'            => 'Convenio Ejemplo',
                'type'            => 'EPS',
                'apb_id'          => 1, // Reemplaza por un ID válido real
                'reps_code'       => 'REPS123',
                'start_date'      => '2024-01-01',
                'end_date'        => '2024-12-31',
                'value_agreement' => 1000000,
                'description'     => 'Descripción del convenio de ejemplo',
            ]
        );



        // 1. Datos simulados para la persona
        $personData = [
            'legal_document_type_id' => 13,
            'document_number'        => '123456789',
            'email_patient'          => 'paciente@example.com',
            'name'                   => 'Juan',
            'second_name'            => 'Carlos',
            'lastname'               => 'Pérez',
            'second_lastname'        => 'Gómez',
            'phone'                  => '3001234567',
            'birthday'               => '1990-01-01',
            'gender'                 => 'M',
        ];

        // 2. Crear o actualizar la persona
        $person = Person::updateOrCreate(
            ['document_number' => $personData['document_number']],
            $personData
        );

        // 3. Crear o actualizar el paciente (usando el mismo ID que la persona)
        $patient = Patient::updateOrCreate(
            ['id' => $person->id],
            [
                'completed' => 1,
                'rh'        => 0,
            ]
        );

        // 4. Crear relación con convenio (puede usar updateOrCreate si puede repetirse)
        $agreementPatient = AgreementsPatients::firstOrCreate(
            [
                'patient_persons_id' => $patient->id,
                'agreement_id'       => 1, // Reemplaza por un ID válido real
            ]
        );

        // 4.1 Crear o actualizar un curso de entrenamiento de ejemplo
        $trainingCourse = TrainingCourse::updateOrCreate(
            ['name' => 'Curso de Ejemplo'],
            [
                'description' => 'Descripción del curso de ejemplo',
                'help_video'  => 'helpVideo/tZgCqNT5pngFi6X2paurtOownUDEQSd3ZDRuXY8B.gif',
                'stopwatch'   => 60,
                'is_active'   => true,
            ]
        );

        // 5. Crear relación con curso
        patient_training_course::firstOrCreate(
            [
                'training_course_id'    => 1,
                'patient_person_id'     => $patient->id,
            ],
            [
                'description'           => 'Observación de ejemplo',
                'date_appointment'      => now(),
                'user_id'               => 1,
                'state'                 => 1,
                'end_course'            => null,
                'commitment'            => null,
                'reason_absence_id'     => null,
                'agreement_patient_id'  => $agreementPatient->id,
                'specialty_id'          => 1,
                'service_id'            => 1,
            ]
        );
    }
}
