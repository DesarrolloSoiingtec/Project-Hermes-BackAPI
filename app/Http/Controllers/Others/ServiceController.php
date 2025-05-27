<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;
use App\Models\Other\ConceptService;
use App\Models\Other\Service;
use Illuminate\Http\JsonResponse;


class ServiceController extends Controller{

    // -------------------------------------------------------------->>>
    // FUNCIONES PARA CREAR, EDITAR Y ELIMINAR -> CONCEPTOS DE SERVICIOS
    // -------------------------------------------------------------->>>

    public function getConceptServices(Request $request){
        $services = ConceptService::where('is_active', true)->get();
        return response()->json($services);
    }

    public function updateConceptService(Request $request){
        log::info("Creating service", $request->all());

        $service = ConceptService::find($request->id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->update([
            'code' => $request->code,
            'name' => $request->name,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Service updated successfully', 'service' => $service]);
    }

    public function deleteConceptService(Request $request){

        Log::info("Disabling service", $request->all());

        $service = ConceptService::find($request->id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->update([
            'is_active' => false,
        ]);

        return response()->json(['message' => 'Service disabled successfully']);
    }

    public function createConceptService(Request $request){
        Log::info("Creating service", $request->all());

        $service = ConceptService::create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        $service->update([
            'code' => $service->id,
        ]);

        return response()->json(['message' => 'Service created successfully', 'service' => $service], 201);
    }

     // -------------------------------------------------------------->>>
    // FUNCIONES PARA CREAR, EDITAR Y ELIMINAR -> SERVICIOS
    // -------------------------------------------------------------->>>

    public function getServices(Request $request){
        $services = Service::with('conceptService')->where('is_active', true)->get();
        return response()->json($services);
    }

    public function createService(Request $request): JsonResponse {
        Log::info("Creating service", $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'concept_id' => 'required|integer|exists:concepts_services,id',
            'level' => 'required|string',
            'gender' => 'required|string',
            'min_age' => 'required|numeric',
            'max_age' => 'required|numeric',
            'cups' => 'required|string|max:10',
            'is_active' => 'required|boolean',
        ]);

        // Limpieza del level (eliminamos paréntesis y contenido)
        $levelClean = preg_replace('/\s*\(.*?\)/', '', $validated['level']);

        // Obtenemos la primera letra del género (en mayúscula)
        $genderInitial = strtoupper(substr($validated['gender'], 0, 1));

        $service = Service::create([
            'name' => $validated['name'],
            'concept_service_id' => $validated['concept_id'],
            'level' => $levelClean,
            'gender' => $genderInitial,
            'min_age' => $validated['min_age'],
            'max_age' => $validated['max_age'],
            'cups' => $validated['cups'],
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'message' => 'Servicio creado exitosamente',
            'data' => $service,
        ], 201);
    }

    public function updateService(Request $request): JsonResponse {
        Log::info("Updating service", $request->all());

        $validated = $request->validate([
            'id' => 'required|integer|exists:services,id',
            'name' => 'required|string|max:255',
            'level' => 'required|string',
            'gender' => 'required|string',
            'min_age' => 'required|numeric',
            'max_age' => 'required|numeric',
            'cups' => 'required|string|max:10',
            'is_active' => 'required|boolean',
        ]);

        $service = Service::find($validated['id']);

        if (!$service) {
            return response()->json([
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        // Actualizar campos (opcionalmente limpiar como hicimos antes)
        $levelClean = preg_replace('/\s*\(.*?\)/', '', $validated['level']);
        $genderInitial = strtoupper(substr($validated['gender'], 0, 1));

        $service->update([
            'name' => $validated['name'],
            'concept_service_id' => $request->concept_id,
            'level' => $levelClean,
            'gender' => $genderInitial,
            'min_age' => $validated['min_age'],
            'max_age' => $validated['max_age'],
            'cups' => $validated['cups'],
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'message' => 'Servicio actualizado exitosamente',
            'data' => $service,
        ]);
    }

    public function deleteService(Request $request): JsonResponse {
        $validated = $request->validate([
            'id' => 'required|integer|exists:services,id',
        ]);

        $service = Service::find($validated['id']);

        if (!$service) {
            return response()->json([
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $service->is_active = false;
        $service->save();

        return response()->json([
            'message' => 'Servicio desactivado exitosamente',
            'data' => $service,
        ]);
    }

    public function getServicesFromConcept(Request $request){
        log::info("Getting services from concept", $request->all());
        $validated = $request->validate([
            'concept_id' => 'required|integer|exists:concepts_services,id',
        ]);

        $services = Service::where('concept_service_id', $validated['concept_id'])
            ->where('is_active', true)
            ->get();

        return response()->json($services);
    }
}
