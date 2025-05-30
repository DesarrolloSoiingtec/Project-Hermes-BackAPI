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
        $apb = APB::with(['agreements' => function($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->get();

        return response()->json([
            'success' => true,
            'data'    => $apb
        ], Response::HTTP_OK);
    }

    public function deleteApb(Request $request): JsonResponse
    {
        Log::info('deleteApb:', $request->all());

        // Validar que el ID exista en el request
        $request->validate([
            'id' => 'required|integer|exists:apb,id'
        ]);

        // Buscar el APB por ID
        $apb = APB::find($request->input('id'));

        // Cambiar is_active a false
        $apb->is_active = false;
        $apb->save();

        return response()->json([
            'success' => true,
            'message' => 'APB desactivado correctamente'
        ], Response::HTTP_OK);
    }

    public function updateApb(Request $request): JsonResponse
    {
        Log::info('updateApb:', $request->all());

        // Validar los datos del request
        $validated = $request->validate([
            'id' => 'required|integer|exists:apb,id',
            'nombre' => 'required|string|max:255',
            'company_type_id' => 'required|integer',
            'numero_documento' => 'required|string|max:20',
            'digito_verificacion' => 'nullable|string|max:2',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'pagina_web' => 'nullable|string|max:255',
            'correo_facturacion' => 'required|email|max:255',
            'nombre_gerente' => 'required|string|max:255',
        ]);

        // Buscar el APB por ID
        $apb = APB::find($request->input('id'));

        // Actualizar los campos
        $apb->update([
            'name' => $validated['nombre'],
            'company_type_id' => $validated['company_type_id'],
            'number' => $validated['numero_documento'],
            'verification_digit' => $validated['digito_verificacion'],
            'address' => $validated['direccion'],
            'phone' => $validated['telefono'],
            'website' => $validated['pagina_web'],
            'billing_email' => $validated['correo_facturacion'],
            'manager_name' => $validated['nombre_gerente'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'APB actualizado correctamente',
            'data' => $apb
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
            'modalidad_contratacion' => 'required|string',
            'servicios_contratados' => 'required|array',
            'nivel_complejidad' => 'required|string',
            'periodicidad_facturacion' => 'required|string',
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
            'description' => $validated['descripcion'],
            'contracting_modality' => $validated['modalidad_contratacion'],
            'contracted_services' => json_encode($validated['servicios_contratados']),
            'complexity_level' => $validated['nivel_complejidad'],
            'billing_periodicity' => $validated['periodicidad_facturacion'],
        ]);

        log::info('Convenio creado:', $agreement->toArray());

        return response()->json([
            'message' => 'Convenio creado exitosamente',
            'data' => $agreement,
        ], 200);
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

