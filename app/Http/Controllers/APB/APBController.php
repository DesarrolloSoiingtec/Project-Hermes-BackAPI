<?php

namespace App\Http\Controllers\APB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\StoreStaffRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\APB\APB;
use App\Models\APB\Agreement;


class APBController extends Controller
{
    public function createApb(Request $request){
        $apb = APB::create([
            'name' => $request->input('nombre'),
            'company_type_id' => $request->input('tipo_documento'),
            'number' => $request->input('numero_documento'),
            'verification_digit' => $request->input('digito_verificacion'),
            'address' => $request->input('direccion'),
            'phone' => $request->input('telefono'),
            'website' => $request->input('pagina_web'),
            'billing_email' => $request->input('correo_facturacion'),
            'manager_name' => $request->input('nombre_gerente'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $apb
        ], Response::HTTP_CREATED);
    }

    public function getApb(Request $request){
        // Eager load agreements for each APB
        $apb = APB::with('agreements')
        ->where('is_active', true)
        ->get();

        return response()->json([
            'success' => true,
            'data'    => $apb
        ], Response::HTTP_OK);
    }

    public function createAgreement(Request $request): JsonResponse
    {
        Log::info('Datos del request:', $request->all());

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'numero_contrato' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'apb_id' => 'required|integer|exists:apb,id',
            'codigo_resp' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'valor_convenio' => 'required|numeric',
            'descripcion' => 'nullable|string',
        ]);

        $agreement = Agreement::create([
            'name' => $validated['nombre'],
            'number' => $validated['numero_contrato'],
            'type' => $validated['tipo'],
            'apb_id' => $validated['apb_id'],
            'reps_code' => $validated['codigo_resp'],
            'start_date' => $validated['fecha_inicio'],
            'end_date' => $validated['fecha_fin'],
            'value_agreement' => $validated['valor_convenio'],
            'description' => $validated['descripcion'] ?? null,
        ]);

        return response()->json([
            'message' => 'Convenio creado exitosamente',
            'data' => $agreement,
        ], 201);
    }

    public function deleteAgreement(Request $request): JsonResponse {
        Log::info('deleteAgreement:', $request->all());

        // Find the agreement by ID
        $agreement = Agreement::find($request->input('id'));
        // Soft delete by setting is_active to false
        $agreement->is_active = false;
        $agreement->save();

        return response()->json([
            'success' => true,
            'message' => 'Agreement deactivated successfully'
        ], Response::HTTP_OK);
    }

}

