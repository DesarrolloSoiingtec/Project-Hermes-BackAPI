<?php

namespace App\Http\Controllers\unauthenticated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Person;
use App\Models\Auth\Patient;
use App\Models\Siau\patient_training_course;
use App\Models\Siau\TrainingCourse;
use App\Models\Siau\CourseFile;
use App\Models\APB\Agreement;
use App\Models\APB\APB;
use App\Models\APB\AgreementPatient;
use App\Models\Siau\ReasonAbsence;
use App\Models\Course\CourseExams;
use App\Models\Course\ExamQuestions;
use App\Models\Course\ExamAnswers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

use App\Mail\CongratulationsMail;
use Illuminate\Support\Facades\Mail;

class NotAuth_ValidationController extends Controller
{
    /**
     * Autenticación pública del paciente capacitado (sin JWT).
     *
     * Valida el número de documento y tipo, busca los cursos activos asignados,
     * recupera los convenios asociados y sus APBs para mostrarlos al paciente.
     *
     * Seguridad aplicada:
     * - Validación estricta de entrada.
     * - Logging limitado para evitar exposición.
     * - Protección recomendada vía `throttle` o token temporal (no incluido aquí).
     *
     * @param Request $request Contiene 'document_number' y 'document_type'
     * @return JsonResponse Datos del paciente, sus cursos y convenios o error.
     */
    public function loginTrained_NotAuth(Request $request): JsonResponse
    {
        // [Validación estricta] Asegura tipos válidos antes de procesar
        $validated = $request->validate([
            'document_number' => ['required', 'regex:/^\d+$/'],
            'document_type'   => ['required', 'integer', 'min:1'],
        ]);

        Log::info("Intento de login público desde IP {$request->ip()} con documento {$validated['document_number']}");

        // [Consulta de persona]
        $person = Person::where('document_number', $validated['document_number'])
            ->where('legal_document_type_id', $validated['document_type'])
            ->where('is_active', true)
            ->first();

        if (!$person) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $personId = $person->id;

        // [Cursos asignados y activos]
        $courseRecord = patient_training_course::where('patient_person_id', $personId)
            ->where('state', '!=', 3)
            ->get();

        if ($courseRecord->isEmpty()) {
            return response()->json(['message' => 'Paciente sin cursos asignados'], 404);
        }

        $trainingCourseIds = $courseRecord->pluck('training_course_id')->toArray();

        // [Cursos asociados]
        $courses = TrainingCourse::whereIn('id', $trainingCourseIds)
        ->where('is_active', true)
        ->get();

        // Validación y actualización de stopwatch según timer en patient_training_course
        foreach ($courses as $course) {
            // Filtra los registros de patient_training_course para este curso
            $ptcForCourse = $courseRecord->where('training_course_id', $course->id);

            if ($ptcForCourse->count() > 1) {
                // Si hay más de uno, toma el que tenga state distinto de 3
                $ptc = $ptcForCourse->first(function ($item) {
                    return $item->state != 3;
                });
            } else {
                // Si solo hay uno, lo tomamos directamente
                $ptc = $ptcForCourse->first();
            }

            // Si encontramos un registro y el timer no está vacío o nulo
            if ($ptc && !is_null($ptc->timer) && $ptc->timer !== '') {
                $course->stopwatch = $ptc->timer;
            }
        }
        log::info("Cursos activos encontrados para user_id $personId: {$courses->count()} cursos.");


        // [Convenios del paciente]
        $agreementIds = AgreementPatient::where('patient_persons_id', $personId)
            ->pluck('agreement_id');

        // [Convenios y sus APBs]
        $agreements = Agreement::with('apb')
            ->whereIn('id', $agreementIds)
            ->get();

        Log::info("Login exitoso para user_id $personId con {$courses->count()} cursos y {$agreements->count()} convenios.");

        return response()->json([
            'message' => 'Ingreso autorizado',
            'data' => [
                'person' => $person,
                'patient_training_course' => $courseRecord,
                'training_courses' => $courses,
                'agreements' => $agreements,
            ]
        ], 200);
    }

    /**
     * Obtener examen activo, preguntas y respuestas para un curso (público sin autenticación).
     *
     * Esta función permite acceder al examen activo de un curso, incluyendo todas sus preguntas
     * y las respuestas asociadas. Es utilizada por usuarios no autenticados (vista pública EPS).
     *
     * Seguridad aplicada:
     * - Validación estricta de entrada (`courseId`).
     * - Logs limitados, sin exponer toda la entrada.
     * - Control de acceso indirecto recomendado con throttle/IP/token (no incluido aquí).
     *
     * @param Request $request Contiene `courseId`.
     * @return JsonResponse Información completa del examen o error si no existe.
     */
    public function getCourseExam_NotAuth(Request $request): JsonResponse
    {
        // [Validación fuerte] Se requiere courseId como entero positivo
        $validated = $request->validate([
            'courseId' => ['required', 'integer', 'min:1'],
        ]);

        // [Logging controlado] Solo IP y courseId para trazabilidad sin exponer datos
        Log::info('Solicitud de examen público para curso ID: ' . $validated['courseId'] . ' desde IP: ' . $request->ip());

        // [Consulta examen] Se busca el examen activo para el curso indicado
        $courseExam = CourseExams::where('training_course_id', $validated['courseId'])
            ->where('is_active', true)
            ->first();

        // Si no se encuentra examen activo, respondemos con 404
        if (!$courseExam) {
            return response()->json([
                'message' => 'No se encontró un examen activo para este curso',
            ], 404);
        }

        // [Preguntas del examen]
        $examQuestions = ExamQuestions::where('course_exams_id', $courseExam->id)
        ->where('is_active', true)
        ->get();

        // [IDs de las preguntas]
        $questionIds = $examQuestions->pluck('id')->toArray();

        // [Respuestas de las preguntas]
        $examAnswers = ExamAnswers::whereIn('exam_question_id', $questionIds)->get();

        // [Logging limitado del resultado] Evita exponer todo en logs
        Log::info("Examen {$courseExam->id} recuperado con {$examQuestions->count()} preguntas y {$examAnswers->count()} respuestas.");

        // [Respuesta final]
        return response()->json([
            'message' => 'Examen encontrado',
            'data' => [
                'courseExam'    => $courseExam,
                'examQuestions' => $examQuestions,
                'examAnswers'   => $examAnswers,
            ],
        ], 200);
    }

    /**
     * Obtener lista de motivos de inasistencia.
     *
     * Esta función expone todos los motivos registrados, filtrando
     * solo aquellos activos. Se usa generalmente en formularios públicos
     * o internos donde se requiere seleccionar un motivo de inasistencia.
     *
     * Seguridad aplicada:
     * - Se devuelve solo información pública (`id`, `name`).
     * - Se filtra por `is_active = true` para evitar mostrar registros inactivos.
     *
     * @return JsonResponse Lista de motivos activos.
     */
    public function getReasons(): JsonResponse
    {
        $reasons = ReasonAbsence::select('id', 'name')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'message' => 'Motivos cargados correctamente',
            'reasons' => $reasons,
        ]);
    }


     /**
     * Obtener archivos públicos asociados a un curso (ruta sin autenticación).
     *
     * Esta función permite acceder a los archivos adjuntos de un curso, especificando su ID.
     * Pensada para ser usada por usuarios sin cuenta (ej: usuarios EPS en vista pública).
     *
     * Seguridad aplicada:
     * - Validación estricta del parámetro `courseId`.
     * - Logging limitado a IP para trazabilidad, sin exponer carga completa.
     *
     * @param Request $request Solicitud HTTP que incluye el parámetro 'courseId'
     * @return JsonResponse Archivos del curso o mensaje de error.
     */
    public function getFiles_NotAuth(Request $request): JsonResponse
    {
        // [Validación] Verificamos que se reciba un ID válido para el curso (entero positivo)
        $validated = $request->validate([
            'courseId' => 'required|integer|min:1|exists:training_courses,id',
        ]);

        // [Log seguro] Solo registramos IP y curso solicitado, no todo el request
        Log::info('Consulta pública de archivos para curso ID: ' . $validated['courseId'] . ' desde IP: ' . $request->ip());

        // [Consulta protegida] Filtramos archivos activos del curso
        $files = CourseFile::select('id', 'path', 'name', 'training_course_id', 'is_active')
            ->where('training_course_id', $validated['courseId'])
            ->where('is_active', true)
            ->get();

        if ($files->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron archivos para este curso',
            ], 404);
        }

        return response()->json([
            'message' => 'Archivos encontrados',
            'files' => $files,
        ]);
    }

    /**
     * Finaliza el curso de un usuario desde una ruta pública.
     *
     * Este endpoint actualiza los datos del curso más reciente del paciente:
     * - Marca el curso como finalizado.
     * - Registra el número de intentos.
     * - Asigna el motivo de inasistencia.
     * - Envía un correo de felicitación.
     *
     * NOTA: Ruta sin autenticación. Asegurar uso controlado mediante throttle o token externo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function courseAnswers_NotAuth(Request $request): JsonResponse{
        Log::info("request: " . json_encode($request->all()));

        // Actualizar validación para incluir los nuevos campos requeridos
        $data = $request->validate([
            'reason_id'     => 'required|integer|min:1',
            'user_id'       => 'required|integer|min:1',
            'attempt_count' => 'required|integer|min:1|max:10',
            'specialty_id'  => 'required',
            'service_id'    => 'required',
            'medical_id'    => 'required',
        ]);

        Log::info("Solicitud pública para actualizar curso de user_id {$data['user_id']} desde IP {$request->ip()}");

        // Buscar el curso específico con todas las coincidencias requeridas
        $ptc = patient_training_course::where([
            'patient_person_id' => $data['user_id'], // busca por ID de paciente
            'specialty_id'     => $data['specialty_id'], // busca por ID de especialidad
            'service_id'       => $data['service_id'], // busca por ID de servicio
            'medical_id'       => $data['medical_id'], // busca por ID de médico
            'state'            => 1  // En caso de haber más de un curso activo, tomamos el que se encuentre pendiente
        ])
        ->latest()
        ->first();

        if (!$ptc) {
            return response()->json([
                'message' => 'No se encontró curso activo con los parámetros especificados para este usuario.',
                'details' => 'Verifique la especialidad, servicio, médico asignados y que el curso esté activo.'
            ], 404);
        }

        // Si hay más de un registro, buscar el que tenga algo en el campo "timer"
        $ptcQuery = patient_training_course::where([
            'patient_person_id' => $data['user_id'],
            'specialty_id'      => $data['specialty_id'],
            'service_id'        => $data['service_id'],
            'medical_id'        => $data['medical_id'],
            'state'             => 1
        ]);

        $ptcResults = $ptcQuery->get();

        if ($ptcResults->count() > 1) {
            // Busca el primero que tenga timer no nulo y no vacío
            $ptc = $ptcResults->first(function ($item) {
            return !is_null($item->timer) && $item->timer !== '';
            });
            // Si no encuentra, toma el primero
            if (!$ptc) {
            $ptc = $ptcResults->first();
            }
        }

        $ptc->update([
            'end_course'        => Carbon::now('America/Bogota'),
            'attempts'          => $data['attempt_count'],
            'reason_absence_id' => $data['reason_id'],
            'state'             => 3,
        ]);

        // Envío de correo si el email es válido
        $person = Person::find($data['user_id']);
        $email = $person?->email_patient;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($email)->send(new CongratulationsMail(
                    $person->name,
                    $person->lastname,
                    'https://clinicamedyser.com.co/'
                ));
                Log::info("Correo de felicitación enviado a $email");
            } catch (\Throwable $e) {
                Log::error("Error al enviar correo a $email: {$e->getMessage()}");
            }
        } else {
            Log::warning("Email inválido o usuario no encontrado para ID {$data['user_id']}");
        }

        return response()->json([
            'message'        => 'Curso actualizado',
            'end_course'     => $ptc->end_course->toDateTimeString(),
            'total_attempts' => $ptc->attempts,
        ]);
    }



public function updateTimer_NotAuth(Request $request): JsonResponse{
    Log::info("request: " . json_encode($request->all()));

    // Convertir timer de segundos a minutos (redondeando hacia abajo)
    $data = $request->validate([
        'user_id'      => 'required|integer|min:1',
        'course_id'    => 'required|integer|min:1',
        'timer'        => 'required|integer|min:0',
        'specialty_id' => 'required|integer|min:1',
        'service_id'   => 'required|integer|min:1',
        'medical_id'   => 'required|integer|min:1',
    ]);

    $minutes = intdiv($data['timer'], 60);

    // Búsqueda progresiva según los criterios dados
    $query = patient_training_course::where('training_course_id', $data['course_id'])
        ->where('patient_person_id', $data['user_id']);

    // Filtros adicionales si hay más de un registro
    $results = $query->get();
    if ($results->count() > 1) {
        $query->where('specialty_id', $data['specialty_id']);
        $results = $query->get();
    }
    if ($results->count() > 1) {
        $query->where('service_id', $data['service_id']);
        $results = $query->get();
    }
    if ($results->count() > 1) {
        $query->where('medical_id', $data['medical_id']);
        $results = $query->get();
    }
    if ($results->count() > 1) {
        $query->where('state', '!=', 3);
        $results = $query->get();
    }

    // Tomar el primer registro encontrado
    $ptc = $results->first();

    if (!$ptc) {
        return response()->json([
            'message' => 'No se encontró el registro del curso para el usuario con los parámetros especificados.'
        ], 404);
    }

    // Actualizar el campo timer (en minutos)
    $ptc->timer = $minutes;
    $ptc->save();

    return response()->json([
        'message' => 'Timer actualizado correctamente',
        'timer_minutes' => $minutes,
        'ptc_id' => $ptc->id,
    ]);
}
}
