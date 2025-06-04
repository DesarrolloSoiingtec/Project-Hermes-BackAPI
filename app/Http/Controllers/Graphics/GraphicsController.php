<?php

namespace App\Http\Controllers\Graphics;

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


class GraphicsController extends Controller
{

    public function getGrapInfo(Request $request): JsonResponse {
        // Obtener fechas del request
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Validar que las fechas sean válidas
        if (!$fechaInicio || !$fechaFin) {
            return response()->json(['error' => 'Fechas no proporcionadas'], 400);
        }

        // Convertir fechas a objetos Carbon
        $fechaInicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($fechaFin)->endOfDay();

        // Verificar que fecha_inicio sea anterior a fecha_fin
        if ($fechaInicio->gt($fechaFin)) {
            return response()->json(['error' => 'La fecha inicial debe ser anterior a la fecha final'], 400);
        }

        // Crear un array con todas las fechas en el rango
        $fechas = [];
        $currentDate = clone $fechaInicio;
        while ($currentDate <= $fechaFin) {
            $fechaKey = $currentDate->format('Y-m-d');
            $fechas[$fechaKey] = [
                'fecha' => $fechaKey,
                'state_1' => 0,
                'state_2' => 0,
                'state_3' => 0
            ];
            $currentDate->addDay();
        }

        // Para cada fecha, calcular los totales acumulados
        foreach (array_keys($fechas) as $fechaStr) {
            $fechaLimite = \Carbon\Carbon::parse($fechaStr)->endOfDay();

            // Obtener conteos para cada estado hasta esta fecha
            $counts = patient_training_course::where('created_at', '<=', $fechaLimite)
                ->whereIn('state', [1, 2, 3])
                ->select('state', DB::raw('COUNT(*) as total'))
                ->groupBy('state')
                ->get();

            // Asignar contadores para este día
            foreach ($counts as $count) {
                $state = $count->state;
                $fechas[$fechaStr]["state_$state"] = $count->total;
            }
        }

        // Convertir a array para la respuesta
        $resultados = array_values($fechas);

        Log::info('Datos de gráfica por estado acumulados:', ['resultados' => $resultados]);

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }

    public function getReasonsGrap(Request $request): JsonResponse {
        // Obtener fechas del request
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Validar que las fechas sean válidas
        if (!$fechaInicio || !$fechaFin) {
            return response()->json(['error' => 'Fechas no proporcionadas'], 400);
        }

        // Convertir fechas a objetos Carbon
        $fechaInicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($fechaFin)->endOfDay();

        // Verificar que fecha_inicio sea anterior a fecha_fin
        if ($fechaInicio->gt($fechaFin)) {
            return response()->json(['error' => 'La fecha inicial debe ser anterior a la fecha final'], 400);
        }

        // Obtener la lista de todos los motivos de ausencia
        $reasons = ReasonAbsence::select('id', 'name')->get();

        // Obtener conteo de registros por reason_absence_id en el rango de fechas
        $reasonCountsQuery = patient_training_course::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereNotNull('reason_absence_id')
            ->select('reason_absence_id', DB::raw('COUNT(*) as total'))
            ->groupBy('reason_absence_id')
            ->get();

        // Convertir a un array asociativo para fácil acceso
        $reasonCounts = [];
        foreach ($reasonCountsQuery as $count) {
            $reasonCounts[$count->reason_absence_id] = $count->total;
        }

        // Preparar los datos para la respuesta
        $reasonsData = $reasons->map(function($reason) use ($reasonCounts) {
            return [
                'id' => $reason->id,
                'name' => $reason->name,
                'count' => $reasonCounts[$reason->id] ?? 0
            ];
        });

        Log::info('Datos de gráfica por motivos de ausencia:', ['resultados' => $reasonsData]);

        return response()->json([
            'success' => true,
            'data' => $reasonsData
        ]);
    }

    public function getAPBandAgreement(Request $request): JsonResponse {
        // Obtener fechas del request
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Validar que las fechas sean válidas
        if (!$fechaInicio || !$fechaFin) {
            return response()->json(['error' => 'Fechas no proporcionadas'], 400);
        }

        // Convertir fechas a objetos Carbon
        $fechaInicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($fechaFin)->endOfDay();

        // Verificar que fecha_inicio sea anterior a fecha_fin
        if ($fechaInicio->gt($fechaFin)) {
            return response()->json(['error' => 'La fecha inicial debe ser anterior a la fecha final'], 400);
        }

        // Contar registros por agreement_patient_id
        $agreementCounts = patient_training_course::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereNotNull('agreement_patient_id')
            ->select('agreement_patient_id', DB::raw('COUNT(*) as total'))
            ->groupBy('agreement_patient_id')
            ->get();

        // Convertir a array asociativo para fácil acceso
        $agreementCountsArray = [];
        foreach ($agreementCounts as $count) {
            $agreementCountsArray[$count->agreement_patient_id] = $count->total;
        }

        // Obtener todos los convenios con sus APBs
        $agreements = Agreement::select('id', 'name', 'apb_id')->get();

        // Obtener todos los APBs
        $apbs = \App\Models\APB\APB::select('id', 'name')->get()->keyBy('id');

        // Preparar los datos para la respuesta
        $resultData = $agreements->map(function($agreement) use ($agreementCountsArray, $apbs) {
            $apbName = isset($apbs[$agreement->apb_id]) ? $apbs[$agreement->apb_id]->name : 'Sin APB';

            return [
                'convenio' => $agreement->name,
                'conteo' => $agreementCountsArray[$agreement->id] ?? 0,
                'apb' => $apbName
            ];
        });

        Log::info('Datos de gráfica por convenios y APB:', ['resultados' => $resultData]);

        return response()->json([
            'success' => true,
            'data' => $resultData
        ]);
    }

}
