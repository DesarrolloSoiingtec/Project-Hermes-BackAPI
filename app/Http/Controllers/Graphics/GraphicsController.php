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
use App\Models\APB\APB;
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
use Carbon\Carbon;

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

    public function getAPBandAgreement(Request $request): JsonResponse
    {
        // Obtener fechas del request
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');


        // Convertir a formato DD/MM/YYYY
        $fechaInicioFormateada = Carbon::parse($fechaInicio)->format('d/m/Y');
        $fechaFinFormateada = Carbon::parse($fechaFin)->format('d/m/Y');


        log::info("Fechas recibidas para gráfica de motivos de ausencia:", ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);

        // Validar que las fechas sean válidas
        if (!$fechaInicio || !$fechaFin) {
            return response()->json(['error' => 'Fechas no proporcionadas'], 400);
        }

        // Convertir fechas a objetos Carbon
        $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = Carbon::parse($fechaFin)->endOfDay();

        // Verificar que fecha_inicio sea anterior a fecha_fin
        if ($fechaInicio->gt($fechaFin)) {
            return response()->json(['error' => 'La fecha inicial debe ser anterior a la fecha final'], 400);
        }

        // Contar registros por agreement_patient_id
        $agreementCounts = AgreementsPatients::select('agreements_patients.agreement_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('patients_training_courses.created_at', [$fechaInicioFormateada, $fechaFinFormateada]) //dd/mm/aaaa
            ->join('patients_training_courses', 'agreements_patients.id', '=', 'patients_training_courses.agreement_patient_id')
            ->groupBy('agreements_patients.agreement_id')
            ->get();

        // Corregir el log (había un error de sintaxis)
        Log::info('Conteos de convenios por paciente:', ['conteos' => $agreementCounts]);

        // Convertir a array asociativo para fácil acceso (corregido)
        $agreementCountsArray = [];
        foreach ($agreementCounts as $count) {
            $agreementCountsArray[$count->agreement_id] = $count->total;
        }

        // Obtener todos los convenios con sus APB
        $agreements = Agreement::select('id', 'name', 'apb_id')->get();

        // Obtener todos los APB
        $apbs = APB::select('id', 'name')->get()->keyBy('id');

        // Preparar los datos para la respuesta
        $resultData = $agreements->map(function ($agreement) use ($agreementCountsArray, $apbs) {
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

//    public function getGrapTable(Request $request): JsonResponse {
//        Log::info("Obteniendo datos para la tabla gráfica", $request->all());
//
//        // Obtener fechas y sede del request
//        $fechaInicio = $request->input('fecha_inicio');
//        $fechaFin = $request->input('fecha_fin');
//        $sedeId = $request->input('sede_id');
//
//        // Formatear a día/mes/año (DD/MM/YYYY)
//        $fechaInicioFormateada = Carbon::parse($fechaInicio)->format('d/m/Y 00:00:00');
//        $fechaFinFormateada = Carbon::parse($fechaFin)->format('d/m/Y 23:59:59');
//
//        // Iniciar la consulta base con el rango de fechas
//        $query = patient_training_course::whereBetween('created_at', [$fechaInicioFormateada, $fechaFinFormateada]);
//
//        // Aplicar filtro de sede solo si no es '%'
//        if ($sedeId !== '%') {
//            $query->where('branch_id', $sedeId);
//        }
//
//        // Agrupar por service_id y contar
//        $resultados = $query->select('service_id', DB::raw('COUNT(*) as total'))
//            ->groupBy('service_id')
//            ->get();
//
//        // Obtener información detallada de los servicios con su concepto relacionado
//        $serviciosIds = $resultados->pluck('service_id')->toArray();
//        log::info("IDs de servicios obtenidos:", ['servicios_ids' => $serviciosIds]);
//
//
//        $servicios = Service::select(
//            'services.id',
//            'services.cups',
//            'services.name as service_name',
//            'services.concept_service_id',
//            'concepts_services.name as concept_service_name',
//            'branch.name as branch_name'
//        )
//            ->join('concepts_services', 'services.concept_service_id', '=', 'concepts_services.id')
//            ->join('patients_training_courses', 'services.id', '=', 'patients_training_courses.service_id')
//            ->join('branch', 'patients_training_courses.branch_id', '=', 'branch.id')
//            ->whereIn('services.id', $serviciosIds)
//            ->get()
//            ->keyBy('id');
//
//        // Formatear los resultados para incluir solo name y concept_service_id
//        $datosFormateados = $resultados->map(function($item) use ($servicios) {
//            $servicio = $servicios[$item->service_id] ?? null;
//            return [
//                'service_cups' => $servicio ? $servicio->cups : 'Sin CUPS',
//                'service_name' => $servicio ? $servicio->service_name : 'Servicio sin nombre',
//                'concept_service_id' => $servicio ? $servicio->concept_service_id : null,
//                'concept_service_name' => $servicio ? $servicio->concept_service_name : 'Sin concepto',
//                'branch_name' => $servicio ? $servicio->branch_name : 'Sin sede',
//                'total' => $item->total
//            ];
//        });
//
//        return response()->json([
//            'success' => true,
//            'data' => $datosFormateados
//        ]);
//    }

public function getGrapTable(Request $request): JsonResponse {
    Log::info("Obteniendo datos para la tabla gráfica", $request->all());

    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');
    $sedeId = $request->input('sede_id');

    $fechaInicioFormateada = Carbon::parse($fechaInicio)->format('Y-m-d 00:00:00');
    $fechaFinFormateada = Carbon::parse($fechaFin)->format('Y-m-d 23:59:59');

    // Consulta agrupando por service_id y branch_id
    $query = patient_training_course::whereBetween('created_at', [$fechaInicioFormateada, $fechaFinFormateada]);
    if ($sedeId !== '%') {
        $query->where('branch_id', $sedeId);
    }

    $resultados = $query->select('service_id', 'branch_id', DB::raw('COUNT(*) as total'))
        ->groupBy('service_id', 'branch_id')
        ->get();

    $serviciosIds = $resultados->pluck('service_id')->unique()->toArray();
    $branchIds = $resultados->pluck('branch_id')->unique()->toArray();

    // Obtener info de servicios y sedes
    $servicios = Service::whereIn('id', $serviciosIds)
        ->with('conceptService') // Asumiendo relación en el modelo Service
        ->get()
        ->keyBy('id');

    $branches = Branch::whereIn('id', $branchIds)->get()->keyBy('id');

    // Formatear resultados
    $datosFormateados = $resultados->map(function($item) use ($servicios, $branches) {
        $servicio = $servicios[$item->service_id] ?? null;
        $branch = $branches[$item->branch_id] ?? null;
        return [
            'service_cups' => $servicio ? $servicio->cups : 'Sin CUPS',
            'service_name' => $servicio ? $servicio->name : 'Servicio sin nombre',
            'concept_service_id' => $servicio ? $servicio->concept_service_id : null,
            'concept_service_name' => $servicio && $servicio->conceptService ? $servicio->conceptService->name : 'Sin concepto',
            'branch_id' => $branch ? $branch->id : null,
            'branch_name' => $branch ? $branch->name : 'Sin sede',
            'total' => $item->total
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $datosFormateados
    ]);
}

}
