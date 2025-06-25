<?php

namespace App\Http\Controllers\Siau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Auth\Person;
use App\Models\Auth\Medical;
use App\Models\Other\MedicalSpecialty;
use Illuminate\Http\JsonResponse;
use App\Models\Auth\Patient;
use App\Models\Siau\ExamQuestion;
use App\Models\Siau\CorseExam;
use App\Models\Siau\ExamAnswer;
use App\Models\Siau\ReasonAbsence;
use App\Models\Siau\TrainingCourse;
use App\Models\Siau\patient_training_course;
use App\Models\Siau\AgreementsPatients;
use App\Models\APB\Agreement;
use App\Models\Other\Branch;
use App\Models\Siau\CourseFile;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderMail;
use App\Mail\PendingTrainingMail;
use App\Models\Other\Service;
use App\Models\Other\Specialty;
use Illuminate\Support\Facades\DB;
use App\Models\User;


class BlockUsers extends Controller
{

    public function createUserBlock(Request $request): JsonResponse {
        log::info("createUserBlock: ", $request->all());

        $responseData = [];
        $fallidos = [];
        $documentTypesAllowed = ['RC', 'TI', 'CC', 'CE', 'P', 'PPT', 'rc', 'ti', 'cc', 'ce', 'p', 'ppt', 'R.C.', 'T.I.', 'C.C.', 'C.E.', 'P.', 'P.P.T.'];

        // Obtener datos generales del request
        $agreementId = $request->input('agreement_id');
        $specialtyId = $request->input('specialty_id');
        $doctorId = $request->input('doctor_id');
        $courseId = $request->input('course_id');

        // Iterar por cada usuario
        foreach($request->input('users') as $userData) {

            if (!in_array($userData['document_type'], $documentTypesAllowed)) {
                $fallidos[] = array_merge($userData, [
                    'error' => 'Documento desconocido'
                ]);
                continue;
            }

            // 2. Validar datos requeridos
            $requiredFields = ['name', 'document_type', 'document_number', 'email', 'phone', 'birth_date', 'gender', 'date', 'time', 'cups', 'sede', 'observation'];
            $missing = [];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    $missing[] = $field;
                }
            }
            if (!empty($missing)) {
                $fallidos[] = array_merge($userData, [
                    'error' => 'Faltan datos para este usuario: ' . implode(', ', $missing)
                ]);
                continue;
            }

            // 3. Validar formato de fecha y hora
            $dateObj = \DateTime::createFromFormat('j/n/Y', $userData['birth_date']);
            $dateAppObj = \DateTime::createFromFormat('j/n/Y', $userData['date']);
            $timeObj = \DateTime::createFromFormat('h:i A', $userData['time']);
            if (!$dateObj) {
                $fallidos[] = array_merge($userData, [
                    'error' => 'Formato de fecha de nacimiento inválido'
                ]);
                continue;
            }
            if (!$dateAppObj) {
                $fallidos[] = array_merge($userData, [
                    'error' => 'Formato de fecha de cita inválido'
                ]);
                continue;
            }
            if (!$timeObj) {
                $fallidos[] = array_merge($userData, [
                    'error' => 'Formato de hora inválido'
                ]);
                continue;
            }


            try {
                DB::beginTransaction();

                // Buscar serviceId por cups
                $service = Service::where('cups', $userData['cups'])->first();
                if (!$service) {
                    throw new \Exception('No se encontró el servicio con el cups: ' . $userData['cups']);
                }
                $serviceId = $service->id;

                // Mapear sede a branchId
                $sedeMap = [
                    '001' => 1,
                    '002' => 2,
                    '003' => 3,
                ];
                $branchId = $sedeMap[$userData['sede']] ?? null;
                if (!$branchId) {
                    throw new \Exception('Sede inválida: ' . $userData['sede']);
                }

                // Separar nombre completo en componentes
                $fullName = trim($userData['name']);
                $nameParts = explode(' ', $fullName);

                // Obtener componentes del nombre con la corrección
                $name = $nameParts[0] ?? '';
                if (count($nameParts) == 2) {
                    // Si solo hay dos palabras, la segunda es el apellido
                    $secondName = '';
                    $lastName = $nameParts[1];
                    $secondLastName = '';
                } else {
                    // Si hay más de dos palabras, seguimos la lógica original
                    $secondName = $nameParts[1] ?? '';
                    $lastName = count($nameParts) >= 3 ? $nameParts[2] : '';
                    $secondLastName = count($nameParts) >= 4 ? $nameParts[3] : '';
                }

                // Mapear tipo de documento
                $legalDocumentTypeId = $this->mapDocumentType($userData['document_type']);

                // Formatear fecha de nacimiento
                $birthday = $this->formatDate($userData['birth_date']);

                // Crear Person
                $person = Person::create([
                    'legal_document_type_id' => $legalDocumentTypeId,
                    'document_number' => $userData['document_number'],
                    'email_patient' => $userData['email'],
                    'name' => $name,
                    'second_name' => $secondName,
                    'lastname' => $lastName,
                    'second_lastname' => $secondLastName,
                    'phone' => $userData['phone'],
                    'birthday' => $birthday,
                    'gender' => $userData['gender'],
                ]);

                log::info("Person pass: ", $person->toArray());

                // Crear Patient
                $patient = Patient::create([
                    'id' => $person->id,
                    'completed' => 0,
                    'is_active' => 1,
                ]);

                log::info("patent pass: ", $patient->toArray());

                // Crear AgreementPatient
                $agreementPatient = AgreementsPatients::create([
                    'patient_persons_id' => $person->id,
                    'agreement_id' => $agreementId,
                ]);
                log::info("AgreementPatient pass: ", $agreementPatient->toArray());

                // Formatear fecha y hora de la cita
                $appointmentDate = $this->combineDateTime($userData['date'], $userData['time']);

                // Obtener el curso de capacitación
                $trainingCourse = TrainingCourse::find($courseId);

                // Crear patient_training_course
                $patientTrainingCourse = patient_training_course::create([
                    'training_course_id' => $courseId,
                    'description' => $userData['observation'],
                    'date_appointment' => $appointmentDate,
                    'user_id' => 1,
                    'patient_person_id' => $person->id,
                    'agreement_patient_id' => $agreementPatient->id,
                    'medical_id' => $doctorId,
                    'specialty_id' => $specialtyId,
                    'service_id' => $serviceId,
                    'branch_id' => $branchId,
                ]);

                log::info("patient_training_course pass: ", $patientTrainingCourse->toArray());

                DB::commit();

                $responseData[] = [
                    'person_id' => $person->id,
                    'status' => 'success',
                    'message' => 'Usuario creado correctamente'
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = $e->getMessage();
                if (str_contains($errorMsg, 'patients_training_courses_branch_id_foreign')) {
                    $errorMsg = 'Sede no encontrada: ' . ($userData['sede'] ?? '');
                }
                $responseData[] = [
                    'document_number' => $userData['document_number'] ?? null,
                    'status' => 'error',
                    'message' => 'Error al crear usuario: ' . $errorMsg
                ];
                $fallidos[] = array_merge($userData, [
                    'error' => $errorMsg
                ]);
                Log::error('Error creating user: ' . $errorMsg);
            }
        }

        return response()->json([
            'message' => 'Proceso completado',
            'results' => $responseData,
            'fallidos' => $fallidos
        ]);
    }

    // Método auxiliar para mapear tipo de documento
    private function mapDocumentType(string $documentType): int {
        $types = [
            'RC' => 11,
            'TI' => 12,
            'CC' => 13,
            'CE' => 14,
            'P' => 15,
            'PPT' => 16,
            'R.C.' => 11,
            'T.I.' => 12,
            'C.C.' => 13,
            'C.E.' => 14,
            'P.' => 15,
            'P.P.T.' => 16,
            'rc' => 11,
            'ti' => 12,
            'cc' => 13,
            'ce' => 14,
            'p' => 15,
            'ppt' => 16,
            'r.c.' => 11,
            't.i.' => 12,
            'c.c.' => 13,
            'c.e.' => 14,
            'p.' => 15,
            'p.p.t.' => 16
        ];

        return $types[$documentType] ?? 1;
    }

    // Método auxiliar para formatear la fecha
    private function formatDate(string $date): string {
        $dateObj = \DateTime::createFromFormat('j/n/Y', $date);
        if ($dateObj) {
            return $dateObj->format('Y-m-d');
        }
        return $date;
    }

    // Método auxiliar para combinar fecha y hora
    private function combineDateTime(string $date, string $time): string {
        $dateObj = \DateTime::createFromFormat('j/n/Y', $date);
        $dateFormatted = $dateObj ? $dateObj->format('Y-m-d') : $date;

        $timeObj = \DateTime::createFromFormat('h:i A', $time);
        $timeFormatted = $timeObj ? $timeObj->format('H:i:s') : $time;

        return $dateFormatted . ' ' . $timeFormatted;
    }

}
