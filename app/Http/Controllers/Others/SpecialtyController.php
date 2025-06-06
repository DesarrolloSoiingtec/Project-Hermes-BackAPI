<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SystemLog;
use App\Models\Other\Specialty;
use App\Models\Other\SubSpecialty;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SpecialtyController extends Controller
{
    public function getSpecialties(Request $request){
        $specialties = Specialty::with(['subspecialties' => function($query) {
            $query->where('is_active', true);
        }])->where('is_active', true)->get();

        return response()->json([
            'message' => 'Lista de especialidades con subespecialidades',
            'data' => $specialties
        ], 200);
    }

    public function createSpecialty(Request $request){
        $specialty = Specialty::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => $request->input('is_active'),
        ]);
        return response()->json([
            'message' => 'Especialidad creada correctamente',
            'data' => $specialty
        ], 200);
    }

    public function deleteSpecialty(Request $request){
        $specialty = Specialty::find($request->input('id'));
        if (!$specialty) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }

        // En lugar de eliminar, desactivar
        $specialty->is_active = false;
        $specialty->save();

        return response()->json([
            'message' => 'Especialidad desactivada correctamente'
        ], 200);
    }

    public function updateSpecialty(Request $request){
        $specialty = Specialty::find($request->input('id'));
        if (!$specialty) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }
        $specialty->name = $request->input('name');
        $specialty->description = $request->input('description');
        $specialty->is_active = $request->input('is_active');
        $specialty->save();
        return response()->json([
            'message' => 'Especialidad actualizada correctamente',
            'data' => $specialty
        ], 200);
    }

    public function createSubspecialty(Request $request): JsonResponse
    {
        Log::info('Creating subspecialty', ['request' => $request->all()]);

        $subspecialty = SubSpecialty::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'specialty_id' => $request->input('specialty_id'),
            'is_active' => true // Por defecto activo
        ]);

        return response()->json([
            'message' => 'Subespecialidad creada correctamente',
            'data' => $subspecialty
        ], 200);
    }

    public function deleteSubspecialty(Request $request): JsonResponse
    {
        Log::info("Deleting subspecialty", ['request' => $request->all()]);

        // Buscar la subespecialidad
        $subspecialty = SubSpecialty::find($request->input('subspecialty_id'));

        // Verificar si existe
        if (!$subspecialty) {
            return response()->json(['message' => 'Subespecialidad no encontrada'], 404);
        }

        // Verificar que pertenezca a la especialidad indicada
        if ($subspecialty->specialty_id != $request->input('specialty_id')) {
            return response()->json(['message' => 'La subespecialidad no pertenece a la especialidad indicada'], 400);
        }

        // Cambiar estado a inactivo
        $subspecialty->is_active = false;
        $subspecialty->save();

        return response()->json([
            'message' => 'Subespecialidad desactivada correctamente',
            'data' => $subspecialty
        ], 200);
    }

}
