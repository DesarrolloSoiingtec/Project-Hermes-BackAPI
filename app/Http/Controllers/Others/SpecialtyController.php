<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemLog;
use App\Models\Other\Specialty;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SpecialtyController extends Controller
{
    public function getSpecialties(Request $request){
        $specialties = Specialty::where('is_active', true)->get();
        return response()->json([
            'message' => 'Lista de especialidades',
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
        Log::info('Datos del request:', $request->all());
        $specialty = Specialty::find($request->input('id'));
        if (!$specialty) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }
        $specialty->delete();
        return response()->json([
            'message' => 'Especialidad eliminada correctamente'
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
}
