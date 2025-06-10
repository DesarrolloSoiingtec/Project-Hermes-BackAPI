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

class SiauController extends Controller
{
// ===================================================================================== >>
//  FUNCIÓN PARA CREAR UN USUARIO Y ASIGNARLO A UN CURSO DE CAPACITACIÓN
//  -> SE ENVIA RECORDATORIO DE CURSO DE CAPACITACIÓN AL CORREO DEL PACIENTE
// ===================================================================================== >>

    /**
     * Crea o actualiza un usuario y lo asigna a un curso de capacitación
     *
     * @param Request $request Datos del formulario que contiene:
     *        - document_type_id: ID del tipo de documento
     *        - document_number: Número de documento
     *        - email: Correo electrónico del paciente
     *        - first_name, second_name: Nombres del paciente
     *        - first_lastname, second_lastname: Apellidos del paciente
     *        - phone: Teléfono de contacto
     *        - birth_date: Fecha de nacimiento
     *        - gender: Género
     *        - agreements: Array de convenios o agreement: ID del convenio
     *        - course_id: ID del curso de capacitación
     *        - observation: Observaciones sobre la asignación
     *        - appointment_date: Fecha de la cita
     *        - doctor_id: ID del médico
     *        - specialty_id: ID de la especialidad
     *        - service_id: ID del servicio
     *        - branch_id: ID de la sucursal
     *
     * @return JsonResponse Respuesta con mensaje de éxito o error
     */
    public function createUser(Request $request)
    {
        Log::info("Request: createUser", $request->all());
        $hour_appointment = $request->input('appointment_time');

        // Validar datos de entrada
        $this->validateUserData($request);

        try {
            // Usar transacción para garantizar integridad de datos
            // Si alguna de las operaciones falla, la transacción se deshacerá automáticamente
            return DB::transaction(function () use ($request, $hour_appointment) {
                // Preparar y procesar datos de la persona
                $person = $this->createOrUpdatePerson($request);
                // Crear o verificar paciente
                $patient = $this->ensurePatientExists($person);

                // Procesar acuerdo y crear relación
                $agreementPatient = $this->processAgreement($request, $person);

                // Crear registro en patients_training_courses
                $trainingCourse = $this->createTrainingCourseAssignment($request, $person, $agreementPatient);
                $appointmentDate = $trainingCourse->date_appointment;

                // Enviar correo de recordatorio
                $this->sendTrainingReminder($person, $patient, $agreementPatient, $appointmentDate, $trainingCourse, $hour_appointment);

                return response()->json([
                    'message' => 'Usuario creado correctamente'
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error("Error al crear usuario: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Válida los datos de entrada para la creación de usuario
     *
     * @param Request $request Datos a validar
     * @return void
     */
    private function validateUserData(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required', // Aquí está el problema
            'document_number' => 'required|string',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'first_lastname' => 'required|string',
            'phone' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|string',
            'course_id' => 'required|exists:training_courses,id',
            'appointment_date' => 'required|date',
        ]);
    }

    /**
     * Crea o actualiza los datos de una persona
     *
     * @param Request $request Datos de la persona
     * @return Person Instancia de la persona creada o actualizada
     */
    private function createOrUpdatePerson(Request $request)
    {
        $incomingData = [
            'legal_document_type_id' => $request->input('document_type_id'),
            'document_number'        => $request->input('document_number'),
            'email_patient'          => $request->input('email'),
            'name'                   => $request->input('first_name'),
            'second_name'            => $request->input('second_name'),
            'lastname'               => $request->input('first_lastname'),
            'second_lastname'        => $request->input('second_lastname'),
            'phone'                  => $request->input('phone'),
            'birthday'               => $request->input('birth_date'),
            'gender'                 => $request->input('gender'),
        ];

        $person = Person::where('document_number', $incomingData['document_number'])->first();

        if ($person) {
            // Comparar campos y actualizar si hay cambios
            $hasChanges = false;
            foreach ($incomingData as $key => $value) {
                if ($person->$key != $value) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges) {
                $person->update($incomingData);
                Log::info("Persona actualizada", $person->toArray());
            } else {
                Log::info("Persona sin cambios");
            }
        } else {
            // Crear nueva persona
            $person = Person::create($incomingData);
            Log::info("Persona creada", $person->toArray());
        }

        return $person;
    }

    /**
     * Asegura que exista un registro de paciente para la persona
     *
     * @param Person $person Persona para la que se creará el paciente
     * @return Patient Instancia del paciente
     */
    private function ensurePatientExists(Person $person)
    {
        $patient = Patient::find($person->id);

        if (!$patient) {
            $patient = Patient::create([
                'id' => $person->id,
                'completed' => 1,
                'rh'        => 0,
            ]);
            Log::info("Paciente creado", $patient->toArray());
        }

        return $patient;
    }

    /**
     * Procesa el acuerdo y crea la relación con el paciente
     *
     * @param Request $request Datos de la solicitud
     * @param Person $person Persona a la que se asignará el acuerdo
     * @return AgreementsPatients Instancia de la relación creada
     */
    private function processAgreement(Request $request, Person $person)
    {
        $agreements = $request->input('agreements');

        if (is_array($agreements) && count($agreements) > 0 && isset(end($agreements)['id'])) {
            $agreementId = end($agreements)['id'];
        } else {
            $agreementId = $request->input('agreement'); // fallback
        }

        Log::info("ID del acuerdo:", ['agreement_id' => $agreementId]);

        $agreementPatient = AgreementsPatients::create([
            'patient_persons_id' => $person->id,
            'agreement_id'       => $agreementId,
        ]);

        Log::info("Registro en agreements_patients creado", $agreementPatient->toArray());

        return $agreementPatient;
    }

    /**
     * Crea la asignación del curso de capacitación al paciente
     *
     * @param Request $request Datos de la solicitud
     * @param Person $person Persona a la que se asignará el curso
     * @param AgreementsPatients $agreementPatient Relación de acuerdo-paciente
     * @return patient_training_course Instancia de la asignación creada
     */
    private function createTrainingCourseAssignment(Request $request, Person $person, AgreementsPatients $agreementPatient)
    {
        $trainingCourse = patient_training_course::create([
            'training_course_id'    => $request->input('course_id'),
            'description'           => $request->input('observation'),
            'date_appointment'      => $request->input('appointment_date'),
            'user_id'               => $request->input('user_id'),
            'state'                 => 1,
            'end_course'            => null,
            'patient_person_id'     => $person->id,
            'commitment'            => null,
            'reason_absence_id'     => null,
            'agreement_patient_id'  => $agreementPatient->id,
            'medical_id'            => $request->input('doctor_id'),
            'specialty_id'          => $request->input('specialty_id'),
            'service_id'            => $request->input('service_id'),
            'branch_id'             => $request->input('branch_id'),
        ]);

        Log::info("Paciente curso creado", $person->toArray());

        return $trainingCourse;
    }

    /**
     * Envía correo de recordatorio para la capacitación
     *
     * @param Person $person Persona a la que se enviará el correo
     * @param string $appointmentDate Fecha de la cita
     * @return void
     */
    private function sendTrainingReminder($person, $patient, $agreementPatient, $appointmentDate, $trainingCourse, $hour_appointment){
        // Obtener URL de configuración en lugar de hardcodear
        $trainingLink = config('app.training_url', 'https://frontend-medyser.space/capacitacion-pedagogica');

        if ($hour_appointment) {
            $hour = \Carbon\Carbon::createFromFormat('H:i', $hour_appointment)->format('h:i A');
        } else {
            $hour = null;
        }

        Mail::to($person->email_patient)
            ->send(new ReminderMail($person, $patient, $agreementPatient, $trainingCourse, $trainingLink, $appointmentDate, $hour_appointment));

        Log::info("Recordatorio de capacitación enviado a {$person->email_patient}");
    }

// ===================================================================================== >>
//  FUNCIÓN PARA ENVIAR RECORDATORIOS A USUARIOS CON CURSOS PENDIENTES
// ===================================================================================== >>

    public function sendMail(Request $request){

        $userId = $request->input('userId');
        $person = Person::find($userId);

        if (!$person || !$person->email_patient) {
            return response()->json(['error' => 'Usuario no encontrado o sin correo'], 404);
        }

        $trainingCourses = patient_training_course::where('patient_person_id', $userId)
            ->where('state', '!=', 3)
            ->get();
        $trainingCoursesCount = $trainingCourses->count();

        Log::info('Person:', $person ? $person->toArray() : []);
        Log::info('TrainingCourses:', $trainingCourses->toArray());
        Log::info('TrainingCoursesCount:', ['count' => $trainingCoursesCount]);

        if ($trainingCoursesCount > 0) {
            $trainingLink = "https://frontend-medyser.space/capacitacion-pedagogica";

            Mail::to($person->email_patient)->send(new PendingTrainingMail(
                $person,
                $trainingCoursesCount,
                $trainingLink
            ));
        }

        return response()->json([
            'person' => $person,
            'training_courses' => $trainingCourses,
            'training_courses_count' => $trainingCoursesCount,
            'mail_sent' => $trainingCoursesCount > 0
        ], 200);
    }


    public function editUser(Request $request){
        Log::info("Request: editUser", $request->all());

        $person = Person::find($request->input('user_id'));

        if (!$person) {
            return response()->json([
            'message' => 'Usuario no encontrado'
            ], 404);
        }

        $incomingData = [
            'legal_document_type_id' => $request->input('document_type_id'),
            'document_number'        => $request->input('document_number'),
            'email_patient'          => $request->input('email_patient'),
            'name'                   => $request->input('first_name'),
            'second_name'            => $request->input('second_name'),
            'lastname'               => $request->input('first_lastname'),
            'second_lastname'        => $request->input('second_lastname'),
            'phone'                  => $request->input('phone'),
            'birthday'               => $request->input('birth_date'),
            'gender'                 => $request->input('gender'),
        ];

        $hasChanges = false;
        foreach ($incomingData as $key => $value) {
            if ($person->$key != $value) {
            $hasChanges = true;
            break;
            }
        }

        if ($hasChanges) {
            $person->update($incomingData);
            Log::info("Usuario actualizado", $person->toArray());
            return response()->json([
            'message' => 'Usuario actualizado correctamente'
            ], 200);
        }

        return response()->json([
            'message' => 'No hay cambios en la información del usuario'
        ], 200);
    }

    public function deleteUser(Request $request)
    {
        $id = $request->input('id');
        $patient = Patient::find($id);
        $person = Person::find($id);

        try {
            if ($patient || $person) {
                if ($patient) {
                    $patient->is_active = false;
                    $patient->save();
                }

                if ($person) {
                    $person->is_active = false;
                    $person->save();
                }

                return response()->json([
                    'message' => 'Registros desactivados correctamente'
                ], 200);
            }

            return response()->json([
                'message' => 'Paciente o persona no encontrado'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error al desactivar registros: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ocurrió un error al intentar desactivar los registros'
            ], 500);
        }
    }


    public function getUsers(Request $request){
        try {
            // Paso 1: Obtener personas con paciente
            $users = Person::with(['patient:id,completed,created_at'])
                ->select([
                    'id', 'legal_document_type_id', 'document_number', 'email_patient',
                    'name', 'second_name', 'lastname', 'second_lastname',
                    'phone', 'birthday', 'gender'
                ])
                ->whereHas('patient', function ($query) {
                    $query->where('is_active', true);
                })
                ->get();

            // Paso 2: Extraer IDs
            $personIds = $users->pluck('id')->toArray();

            // Paso 3: Obtener cursos de formación relacionados
            $trainingRecords = patient_training_course::with('trainingCourse')
                ->whereIn('patient_person_id', $personIds)
                ->get();

            // Determinar el estado general basado en los valores de "state" en $trainingRecords
            $estado = null;

            $estado = $users->mapWithKeys(function ($user) use ($trainingRecords) {
                $userTrainingStates = $trainingRecords
                    ->where('patient_person_id', $user->id)
                    ->pluck('state');

                $allCompleted = $userTrainingStates->every(fn($state) => $state === 3);

                // Update the "completed" field in the Patient model
                $patient = Patient::find($user->id);
                if ($patient) {
                    $patient->completed = $allCompleted;
                    $patient->save();
                }

                return [$user->id => $allCompleted ? 3 : 1];
            });

            Log::info("Estado determinado:", ['estado' => $estado]);

            // Paso 4: Mapear los usuarios como antes
            $formattedUsers = $users->map(function ($person) {
                return [
                    'id' => $person->id,
                    'legal_document_type_id' => $person->legal_document_type_id,
                    'document_number' => $person->document_number,
                    'email_patient' => $person->email_patient,
                    'name' => $person->name,
                    'second_name' => $person->second_name,
                    'lastname' => $person->lastname,
                    'second_lastname' => $person->second_lastname,
                    'phone' => $person->phone,
                    'birthday' => $person->birthday,
                    'gender' => $person->gender,
                    'completed' => optional($person->patient)->completed,
                    'patient_created_at' => optional($person->patient)->created_at,
                ];
            });

            Log::info("Usuarios obtenidos: {$formattedUsers->count()}");
            Log::info("Cursos obtenidos: {$trainingRecords->count()}");

            $services = Service::with('conceptService')->where('is_active', true)->get();
            $specialties = Specialty::where('is_active', true)->get();
            $reason = ReasonAbsence::where('is_active', true)->get();

            return response()->json([
                'users' => $formattedUsers,
                'training_courses' => $trainingRecords,
                'services' => $services,
                'reason' => $reason,
                'specialties' => $specialties
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error en getUsers: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ocurrió un error al obtener los datos'], 500);
        }
    }


    public function Trained(Request $request){
        $documentTypeId = $request->input('document_type_id');
        $documentNumber = $request->input('document_number');
        $person = Person::where('document_number', $documentNumber)->first();

        if ($person) {
            if ($person->legal_document_type_id != $documentTypeId) {
                return response()->json([
                    'message' => 'El número de documento ya existe pero con un tipo de documento diferente.',
                ], 422);
            }
        }
        return response()->json([
            'message' => 'El usuario ya existe.',
            'person' => $person,
        ], 200);
    }

    public function getAgreementsPatient(Request $request){
        Log::info("Request: getAgreementsPatient", $request->all());

        // 1. Obtener el ID del usuario desde el request
        $personId = $request->input('user_id') ?? $request->input('id');
        Log::info("Persona ID recibida", ['id' => $personId]);

        // 2. Buscar todos los pacientes asociados a ese person_id
        $patients = Patient::where('id', $personId)->pluck('id');
        Log::info("Pacientes encontrados", $patients->toArray());

        if ($patients->isEmpty()) {
            return response()->json([
                'exists' => false,
                'message' => 'No se encontraron registros de paciente para esta persona.',
            ], 404);
        }

        // 3. Buscar relaciones en agreements_patients con esos patient_persons_id
        $agreementIds = AgreementsPatients::whereIn('patient_persons_id', $patients)
            ->pluck('agreement_id');

        Log::info("IDs de convenios encontrados", $agreementIds->toArray());

        if ($agreementIds->isEmpty()) {
            return response()->json([
                'exists' => false,
                'message' => 'Sin convenios para este usuario.',
            ], 404);
        }

        // 4. Obtener los convenios asociados
        $agreements = Agreement::whereIn('id', $agreementIds)
        ->where('is_active', true)
        ->get();
        Log::info("Convenios obtenidos", $agreements->toArray());

        return response()->json([
            'exists' => true,
            'agreements' => $agreements
        ], 200);
    }

    // ===================================================================================== >>
    // RUTAS CREACIÓN DE CURSOS
    // ===================================================================================== >>

    public function createCourse(Request $request){
        // Validación rápida (opcional pero recomendable)
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'timeLimit' => 'nullable|integer',
            'videoHelp' => 'nullable|file|mimes:mp4,avi,mov,gif,png,jpg,jpeg',
        ]);
        // Inicializar datos
        $data = $request->only(['name', 'description']);
        $data['stopwatch'] = $request->timeLimit;
        $data['is_active'] = true; // Puedes ajustar esto según lógica de negocio
        // Procesar archivo si existe
        if ($request->hasFile('videoHelp')) {
            $path = Storage::putFile('helpVideo', $request->file('videoHelp'));
            $data['help_video'] = $path;
        }
        // Crear el curso
        $course = TrainingCourse::create($data);
        return response()->json([
            'message' => 200,
            'course' => $course,
        ]);
    }

    public function updateCourse(Request $request): JsonResponse
    {
        Log::info("Request: updateCourse", $request->all());

        // Buscar el curso por ID
        $course = TrainingCourse::find($request->input('course_id'));

        if (!$course) {
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 404);
        }

        // Validar los datos de entrada
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'timeLimit' => 'required|integer',
            'videoHelp' => 'sometimes|file',
        ]);

        // Actualizar los campos básicos
        $course->name = $request->input('name');
        $course->description = $request->input('description');
        $course->stopwatch = $request->input('timeLimit');

        // Manejar el archivo de video si está presente
        if ($request->hasFile('videoHelp')) {
            $path = Storage::putFile('helpVideos', $request->file('videoHelp'));
            $course->help_video = $path;
        }

        $course->save();

        Log::info("Curso actualizado correctamente", $course->toArray());

        return response()->json([
            'message' => 'Curso actualizado correctamente',
            'course' => $course,
        ], 200);
    }

    public function disableEnableCourse(Request $request): JsonResponse {
        Log::info("Request: disableEnableCourse", $request->all());

        // Buscar el curso por ID
        $course = TrainingCourse::find($request->input('course_id'));

        if (!$course) {
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 404);
        }

        // Cambiar el estado de is_active
        $course->is_active = !$course->is_active;
        $course->save();

        Log::info("Curso actualizado correctamente", $course->toArray());

        return response()->json([
            'message' => 'Curso actualizado correctamente',
            'course' => $course,
        ], 200);
    }

    public function getCourses(Request $request){
        // Obtener los cursos
        $courses = TrainingCourse::select('id', 'name', 'description', 'help_video', 'stopwatch', 'is_active')
            ->get();

        // Obtener el conteo de usuarios por curso
        $userCounts = patient_training_course::select('training_course_id', DB::raw('COUNT(*) as count'))
            ->where('state', '!=', 3)
            ->groupBy('training_course_id')
            ->pluck('count', 'training_course_id')
            ->toArray();

        // Añadir el conteo a cada curso
        $coursesWithCount = $courses->map(function ($course) use ($userCounts) {
            // Convertir a array y añadir el conteo de usuarios
            $courseArray = $course->toArray();
            $courseArray['user_count'] = $userCounts[$course->id] ?? 0;
            return $courseArray;
        });

        return response()->json([
            'message' => 200,
            'courses' => $coursesWithCount,
        ]);
    }

    public function deleteCourseFromUser(Request $request){
        Log::info("Request: deleteCourseFromUser", $request->all());
        $id = $request->input('id');
        $patientPersonId = $request->input('patient_person_id');

        // Buscar el registro por ID
        $trainingCourse = patient_training_course::find($id);

        if ($trainingCourse && $trainingCourse->patient_person_id == $patientPersonId) {
            // Almacenar el agreement_patient_id antes de eliminar
            $agreementPatientId = $trainingCourse->agreement_patient_id;

            // Eliminar el curso
            $trainingCourse->delete();

            // Buscar y eliminar el registro en agreements_patients si existe
            if ($agreementPatientId) {
                $agreementPatient = AgreementsPatients::find($agreementPatientId);
                if ($agreementPatient) {
                    $agreementPatient->delete();
                }
            }

            return response()->json([
                'message' => 'Curso eliminado correctamente del usuario.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'No se encontró el registro o no coincide el paciente.'
            ], 404);
        }
    }

    public function getReasons (request $request){
        $reasons = ReasonAbsence::select('id', 'name', 'is_active')
        ->get();

        return response()->json([
            'message' => 200,
            'reasons' => $reasons,
        ]);
    }

    // ===================================================================================== >>
    // RUTAS CREACIÓN DE PREGUNTAS, RESPUESTAS Y ASIGNARLAS A UN CURSO
    // ===================================================================================== >>

    public function getQuestion(Request $request){
        $questions = ExamQuestion::select('id', 'question', 'help_text', 'multiple_answer', 'value_question', 'is_active', 'course_exams_id', 'video_ayuda')
        ->where('is_active', true)
        ->get();
        Log::info('Datos del request:', $questions->all());

        return response()->json([
            'message' => 200,
            'questions' => $questions,
        ]);
    }

    public function deleteQuestion(Request $request){
        Log::info("deleteQuestion: ", $request->all());

        try {
            // Validar los datos de entrada
            $request->validate([
                'question_id' => 'required|integer',
                'questionnaire_id' => 'required|integer',
            ]);

            // Buscar la pregunta específica
            $question = ExamQuestion::where('id', $request->input('question_id'))
                ->where('course_exams_id', $request->input('questionnaire_id'))
                ->first();

            if (!$question) {
                return response()->json([
                    'message' => 'No se encontró la pregunta especificada',
                ], 404);
            }

            // Actualizar el estado is_active a false
            $question->is_active = false;
            $question->save();

            Log::info("Pregunta desactivada correctamente: ID {$question->id}");

            return response()->json([
                'message' => 'Pregunta desactivada correctamente',
                'question' => $question
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al desactivar pregunta: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al desactivar la pregunta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createQuestion(Request $request){
        Log::info("Request: ",$request->all());

        $question = ExamQuestion::create([
            'question' => $request->input('text'),
            'help_text' => null, // Puedes ajustar esto si necesitas un valor por defecto
            'multiple_answer' => filter_var($request->input('multipleAnswers'), FILTER_VALIDATE_BOOLEAN),
            'value_question' => null, // Ajusta según sea necesario
            'is_active' => true, // Por defecto activo
            'course_exams_id' => $request->input('questionnaire_id'),
            'video_ayuda' => null, // Ajusta según sea necesario
        ]);

        // Obtener toda la tabla actualizada
        $questions = ExamQuestion::all();;
        log::info("Pregunta creada", $questions->all());
        // Retornar la respuesta con la tabla actualizada
        return response()->json([
            'message' => 'Pregunta creada correctamente',
            'questions' => $questions,
        ], 200);
    }

    public function updateQuestion(Request $request): JsonResponse
    {
        Log::info("Request: updateQuestion", $request->all());

        // Validar datos de entrada
        $request->validate([
            'question_id' => 'required|integer',
            'questionnaire_id' => 'required|integer',
            'text' => 'required|string',
        ]);

        try {
            // Buscar la pregunta específica que pertenece al cuestionario indicado
            $question = ExamQuestion::where('id', $request->input('question_id'))
                                  ->where('course_exams_id', $request->input('questionnaire_id'))
                                  ->first();

            if (!$question) {
                return response()->json([
                    'message' => 'No se encontró la pregunta especificada en el cuestionario',
                ], 404);
            }

            // Actualizar el texto de la pregunta
            $question->question = $request->input('text');
            $question->save();

            Log::info("Pregunta actualizada correctamente", $question->toArray());

            return response()->json([
                'message' => 'Pregunta actualizada correctamente',
                'question' => $question,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al actualizar pregunta: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar la pregunta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionnaires(Request $request){
        $questionnaires = CorseExam::select('id', 'name', 'description', 'is_active', 'training_course_id')
        ->get();

        return response()->json([
            'message' => 200,
            'questionnaires' => $questionnaires,
        ]);
    }

    public function createQuestionnaire(Request $request){
        Log::info("Request: ",$request->all());

        $questionnaire = CorseExam::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => true,
            'training_course_id' => $request->input('course_id'),
            'video_ayuda' => null, // Ajusta según sea necesario
        ]);

        // Obtener el ID del cuestionario recién creado y el ID del curso
        $newQuestionnaireId = $questionnaire->id;
        $courseId = $request->input('course_id');

        // Actualizar todos los cuestionarios del mismo curso para que is_active sea false, excepto el recién creado
        CorseExam::where('training_course_id', $courseId)
            ->where('id', '!=', $newQuestionnaireId)
            ->update(['is_active' => false]);

        // Obtener toda la tabla actualizada
        $questionnaires = CorseExam::all();
        Log::info("se creo el cuestionario", $questionnaires->all());

        // Retornar la respuesta con la tabla actualizada
        return response()->json([
            'message' => 'Cuestionario creado correctamente',
            'questionnaires' => $questionnaires,
        ], 200);
    }

    public function updateQuestionnaire(Request $request): JsonResponse
    {
        Log::info("Request: updateQuestionnaire", $request->all());

        // Validar datos de entrada
        $request->validate([
            'questionnaire_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        // Buscar el cuestionario por ID
        $questionnaire = CorseExam::find($request->input('questionnaire_id'));

        if (!$questionnaire) {
            return response()->json([
                'message' => 'Cuestionario no encontrado',
            ], 404);
        }

        // Actualizar los campos
        $questionnaire->name = $request->input('name');
        $questionnaire->description = $request->input('description');
        $questionnaire->save();

        Log::info("Cuestionario actualizado correctamente", $questionnaire->toArray());

        return response()->json([
            'message' => 'Cuestionario actualizado correctamente',
            'questionnaire' => $questionnaire,
        ], 200);
    }

    public function activateQuestionnaire(Request $request){
        Log::info("llegue a activar cuestionario", $request->all());
        $courseId = $request->input('course_id');
        $questionnaireId = $request->input('questionnaire_id');
        $courseId = $request->input('course_id');
        $questionnaireId = $request->input('questionnaire_id');

        // Activar el cuestionario especificado
        CorseExam::where('id', $questionnaireId)
            ->where('training_course_id', $courseId)
            ->update(['is_active' => true]);

        // Desactivar todos los demás cuestionarios del mismo curso
        CorseExam::where('training_course_id', $courseId)
            ->where('id', '!=', $questionnaireId)
            ->update(['is_active' => false]);

        // Obtener todos los cuestionarios actualizados
        $questionnaires = CorseExam::where('training_course_id', $courseId)->get();
        Log::info("Cuestionarios actualizados", $questionnaires->all());

        return response()->json([
            'message' => 'Cuestionario activado correctamente',
            'questionnaires' => $questionnaires,
        ], 200);
    }

    //------------------------------------------------------------------------------------>>
    public function createAnswers (Request $request){
        log::info("Request createAnswers: ",$request->all());
        $answer = ExamAnswer::create([
            'answer' => $request->input('text'),
            'is_correct' => filter_var($request->input('is_correct'), FILTER_VALIDATE_BOOLEAN),
            'is_active' => true, // Por defecto activo
            'exam_question_id' => $request->input('question_id'),
            'help_video' => null, // Ajusta según sea necesario
        ]);

        // Obtener todas las respuestas actualizadas
        $answers = ExamAnswer::where('is_active', true)->get();
        // Retornar la respuesta con la tabla actualizada
        return response()->json([
            'message' => 'Respuesta creada correctamente',
            'answers' => $answers,
        ], 200);

    }

    public function getAnswers (Request $request){
        $answers = ExamAnswer::where('is_active', true)->get();
        Log::info("se creo la respuesta", $answers->all());

        return response()->json([
            'message' => 200,
            'answers' => $answers,
        ]);
    }

    public function selectAnswers(Request $request): JsonResponse
    {
        Log::info("Request selectAnswers: ", $request->all());

        $questionId = $request->input('question_id');
        $answerId = $request->input('answer_id');

        // Verificar que los datos existan
        if (!$questionId || !$answerId) {
            return response()->json([
                'message' => 'Se requieren question_id y answer_id'
            ], 400);
        }

        // Buscar la respuesta específica
        $answer = ExamAnswer::where('id', $answerId)
            ->where('exam_question_id', $questionId)
            ->first();

        if (!$answer) {
            return response()->json([
                'message' => 'La respuesta especificada no existe para esta pregunta'
            ], 404);
        }

        // Alternar el estado is_correct (de true a false o de false a true)
        $answer->is_correct = !$answer->is_correct;
        $answer->save();

        $estado = $answer->is_correct ? 'correcta' : 'incorrecta';

        Log::info("Respuesta marcada como {$estado}", [
            'question_id' => $questionId,
            'answer_id' => $answerId,
            'is_correct' => $answer->is_correct
        ]);

        return response()->json([
            'message' => "Respuesta marcada como {$estado} exitosamente",
            'answer' => $answer
        ], 200);
    }

    public function deleteAnswers(Request $request): JsonResponse
    {
        Log::info("Request deleteAnswers: ", $request->all());

        $questionId = $request->input('question_id');
        $answerId = $request->input('answer_id');

        // Verificar que los datos existan
        if (!$questionId || !$answerId) {
            return response()->json([
                'message' => 'Se requieren question_id y answer_id'
            ], 400);
        }

        // Buscar la respuesta específica
        $answer = ExamAnswer::where('id', $answerId)
            ->where('exam_question_id', $questionId)
            ->first();

        if (!$answer) {
            return response()->json([
                'message' => 'La respuesta especificada no existe para esta pregunta'
            ], 404);
        }

        // Cambiar el estado is_active a false
        $answer->is_active = false;
        $answer->save();

        Log::info("Respuesta desactivada", [
            'question_id' => $questionId,
            'answer_id' => $answerId
        ]);

        return response()->json([
            'message' => "Respuesta desactivada exitosamente",
            'answer' => $answer
        ], 200);
    }

    // ===================================================================================== >>
    // RUTAS CREACIÓN Y ASIGNACIÓN DE ARCHIVOS A UN CURSO
    // ===================================================================================== >>

    public function assignFile(Request $request){
        log::info("Request111: ",$request->all());
        // 1) Validación

        log::info("Sali de la validación");

        $courseId = $request->input('course_id');

        // 2) Iteramos sobre cada UploadedFile
        foreach ($request->file('files') as $uploaded) {
            // 2.1) Guardamos en disk 'public' dentro de fileCourse
            //      → ruta final: storage/app/public/fileCourse/…
            $storedPath = $uploaded->store('fileCourse', 'public');

            // 2.2) Creamos registro en DB
            CourseFile::create([
                'path'               => $storedPath,
                'name'               => $uploaded->getClientOriginalName(),
                'training_course_id' => $courseId,
                'is_active'          => true,
            ]);
        }

        // 3) Respuesta
        return response()->json([
            'message' => 'Archivos subidos y registrados correctamente.'
        ], 201);
    }

    public function getFiles(Request $request){
        $files = CourseFile::select('id', 'path', 'name', 'training_course_id', 'is_active')
        ->get();

        return response()->json([
            'message' => 200,
            'files' => $files,
        ]);
    }

    public function deleteFile(Request $request): JsonResponse
    {
        Log::info("Request: deleteFile", $request->all());

        $validated = $request->validate([
            'course_id' => 'required|integer',
            'file_ids' => 'required|array',
            'file_ids.*' => 'integer'
        ]);

        try {
            // Buscar los archivos a eliminar
            $filesToDelete = CourseFile::where('training_course_id', $validated['course_id'])
                ->whereIn('id', $validated['file_ids'])
                ->get();

            if ($filesToDelete->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron archivos para eliminar',
                ], 404);
            }

            // Eliminar archivos físicos
            foreach ($filesToDelete as $file) {
                // Verificar que la ruta no sea nula
                if (!empty($file->file_path)) {
                    if (Storage::exists($file->file_path)) {
                        Storage::delete($file->file_path);
                    }
                }
            }

            // Eliminar registros de la base de datos
            $deleted = CourseFile::where('training_course_id', $validated['course_id'])
                ->whereIn('id', $validated['file_ids'])
                ->delete();

            return response()->json([
                'message' => 'Archivos eliminados correctamente',
                'count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al eliminar archivos: " . $e->getMessage());

            return response()->json([
                'message' => 'Error al eliminar los archivos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===================================================================================== >>
    // RUTAS CREACIÓN Y VISTA DE RAZONES
    // ===================================================================================== >>

    public function updateCheckReasons (request $request){
        $reason = ReasonAbsence::find($request->input('reason_id'));

        if ($reason) {
            $reason->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $reason->save();

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'reason' => $reason,
            ], 200);
        }

        return response()->json([
            'message' => 'Razón no encontrada',
        ], 404);
    }

    public function updateReasons (request $request){
        $reason = ReasonAbsence::find($request->input('reason_id'));

        if ($reason) {
            $reason->name = $request->input('name');
            $reason->save();

            return response()->json([
                'message' => 'Razón actualizada correctamente',
                'reason' => $reason,
            ], 200);
        }

        return response()->json([
            'message' => 'Razón no encontrada',
        ], 404);

    }

    public function storeReasons (request $request){
        $reason = ReasonAbsence::create([
            'name' => $request->input('name'),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Razón creada correctamente',
            'reason' => $reason,
        ], 200);
    }

    public function deleteReason (request $request){
        $reason = ReasonAbsence::find($request->input('reason_id'));

        if ($reason) {
            $reason->delete();

            return response()->json([
                'message' => 'Razón eliminada correctamente',
            ], 200);
        }

        return response()->json([
            'message' => 'Razón no encontrada',
        ], 404);
    }

    // ===================================================================================== >>
    // RUTA PARA OBTENER MEDICOS POR ESPECIALIDAD
    // ===================================================================================== >>

    public function getMedicalFromSpecialties(Request $request): JsonResponse
    {
        Log::info("Request getMedicalFromSpecialties: ", $request->all());

        $specialtyId = $request->input('specialty_id');

        $doctors = MedicalSpecialty::where('specialty_id', $specialtyId)
            ->with(['medical.person' => function($query) {
                $query->select('id', 'name', 'lastname');
            }])
            ->get()
            ->map(function($medicalSpecialty) {
                // Agregar logs para debug
                Log::info("MedicalSpecialty:", [
                    'id' => $medicalSpecialty->id,
                    'medical' => $medicalSpecialty->medical,
                    'person' => $medicalSpecialty->medical ? $medicalSpecialty->medical->person : null
                ]);

                // Validar que existan las relaciones
                if (!$medicalSpecialty->medical || !$medicalSpecialty->medical->person) {
                    return null;
                }

                return [
                    'id' => $medicalSpecialty->medical->id,
                    'name' => $medicalSpecialty->medical->person->name,
                    'lastname' => $medicalSpecialty->medical->person->lastname
                ];
            })
            ->filter(); // Eliminar los valores null

        return response()->json([
            'message' => 'Médicos encontrados',
            'doctors' => $doctors
        ], 200);
    }


    public function getBranches(Request $request): JsonResponse{
        log::info("Llegue a getBranches");

        $branches = Branch::all();

        return response()->json([
            'message' => 'Sucursales obtenidas correctamente',
            'branches' => $branches,
        ], 200);
    }

    public function createBranch(Request $request): JsonResponse{
        log::info("Request: createBranch", $request->all());

        // Validar los datos del request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'is_active' => 'required|boolean',
            'manager_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        // Crear la sucursal
        $branch = Branch::create($validatedData);

        log::info("Branch created successfully", $branch->toArray());

        return response()->json([
            'message' => 'Branch created successfully',
            'branch' => $branch,
        ], 201);
    }

    public function updateBranch(Request $request): JsonResponse{
        log::info("Request: updateBranch", $request->all());

        // Buscar la sucursal por ID
        $branch = Branch::find($request->input('id'));

        if ($branch) {
            // Actualizar la información de la sucursal
            $branch->update($request->only([
                'name',
                'address',
                'city',
                'state',
                'country',
                'phone',
                'email',
                'manager_name',
                'is_active',
            ]));

            log::info("Branch updated successfully", $branch->toArray());

            return response()->json([
                'message' => 'Branch updated successfully',
                'branch' => $branch,
            ], 200);
        }

        return response()->json([
            'message' => 'Branch not found',
        ], 404);
    }

    public function deactivateBranch(Request $request): JsonResponse{
        log::info("Request: deleteBranch", $request->all());

        $branch = Branch::find($request->input('id'));

        if ($branch) {
            $branch->is_active = false;
            $branch->save();

            log::info("Branch deactivated successfully", $branch->toArray());

            return response()->json([
            'message' => 'Branch deactivated successfully',
            'branch' => $branch,
            ], 200);
        }

        return response()->json([
            'message' => 'Branch not found',
        ], 404);
    }

    public function activateBranch(Request $request): JsonResponse{
        log::info("Request: activateBranch", $request->all());

        $branch = Branch::find($request->input('id'));

        if ($branch) {
            $branch->is_active = true;
            $branch->save();

            log::info("Branch activated successfully", $branch->toArray());

            return response()->json([
                'message' => 'Branch activated successfully',
                'branch' => $branch,
            ], 200);
        }

        return response()->json([
            'message' => 'Branch not found',
        ], 404);
    }

    public function getActiveBranches(Request $request): JsonResponse{
        log::info("Llegue a getActiveBranches");

        $branches = Branch::where('is_active', true)->get();

        return response()->json([
            'message' => 'Sucursales obtenidas correctamente',
            'branches' => $branches,
        ], 200);
    }

    public function addVisualHelp(Request $request): JsonResponse{
        Log::info("Request: addVisualHelp", $request->all());

        // Validar los datos de entrada
        $request->validate([
            'question_id' => 'required|integer',
            'questionnaire_id' => 'required|integer',
            'file' => 'required|file|mimes:mp4,avi,mov,gif,png,jpg,jpeg',
        ]);

        try {
            // Almacenar el archivo en el Storage
            $path = Storage::putFile('helpVideo', $request->file('file'));

            // Buscar la pregunta del examen
            $question = ExamQuestion::where('id', $request->input('question_id'))
                ->where('course_exams_id', $request->input('questionnaire_id'))
                ->first();

            if (!$question) {
                return response()->json([
                    'message' => 'No se encontró la pregunta especificada',
                ], 404);
            }

            // Actualizar el campo video_ayuda con la ruta del archivo
            $question->video_ayuda = $path;
            $question->save();

            Log::info("Ayuda visual actualizada para la pregunta ID: {$question->id}", [
                'path' => $path
            ]);

            return response()->json([
                'message' => 'Archivo multimedia almacenado correctamente',
                'path' => $path,
                'question' => $question
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al almacenar ayuda visual: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al almacenar el archivo multimedia: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sqlRequest(Request $request): JsonResponse {
        Log::info("Request: sqlRequest", $request->all());

        // Validar que se proporcione una consulta
        $request->validate([
            'query' => 'required|string'
        ]);

        $query = trim($request->input('query'));

        // Verificar si la consulta es SELECT o UPDATE
        $isSelect = stripos($query, 'SELECT') === 0;
        $isUpdate = stripos($query, 'UPDATE') === 0;

        if (!$isSelect && !$isUpdate) {
            return response()->json([
                'error' => 'Solo se permiten consultas SELECT y UPDATE'
            ], 403);
        }

        // Verificar que no contenga palabras clave peligrosas
        $dangerousKeywords = ['DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT',
                             'GRANT', 'REVOKE', 'MERGE', 'EXECUTE', 'EXEC'];

        foreach ($dangerousKeywords as $keyword) {
            if (stripos($query, $keyword) !== false) {
                return response()->json([
                    'error' => 'La consulta contiene operaciones no permitidas'
                ], 403);
            }
        }

        try {
            $results = null;
            $affectedRows = 0;

            // Ejecutar la consulta según su tipo
            if ($isSelect) {
                $results = DB::select($query);
            } else { // Es UPDATE
                $affectedRows = DB::update($query);
            }

            // Registrar la consulta exitosa
            Log::info("Consulta SQL ejecutada con éxito", [
                'query' => $query,
                'tipo' => $isSelect ? 'SELECT' : 'UPDATE',
                'filas' => $isSelect ? count($results) : $affectedRows
            ]);

            return response()->json([
                'success' => true,
                'tipo' => $isSelect ? 'SELECT' : 'UPDATE',
                'resultados' => $isSelect ? $results : null,
                'filas_afectadas' => $isSelect ? 0 : $affectedRows
            ], 200);
        } catch (\Exception $e) {
            // Registrar el error con detalles
            Log::error("Error al ejecutar consulta SQL", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            // Detectar problemas comunes de tipos de datos
            $errorMsg = $e->getMessage();
            $sugerencia = '';

            if (stripos($errorMsg, 'character varying = integer') !== false) {
                $sugerencia = 'Los tipos de datos no coinciden. Intenta convertir el número a texto: WHERE number = \'900123456\'';
            }

            return response()->json([
                'error' => 'Error al ejecutar la consulta: ' . $e->getMessage(),
                'sugerencia' => $sugerencia
            ], 500);
        }
    }

    public function validateAdminPassword(Request $request): JsonResponse {
        Log::info("Request: validateAdminPassword", $request->all());

        // Validar datos de entrada
        $request->validate([
            'password' => 'required|string',
            'user_id' => 'required'
        ]);

        // Buscar el usuario por ID
        $user = User::find($request->input('user_id'));

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Verificar la contraseña
        if (\Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Las contraseñas coinciden'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Las contraseñas no coinciden'
        ], 401);
    }

}
