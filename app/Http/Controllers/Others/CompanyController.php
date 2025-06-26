<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Other\EconomicActivity;
use App\Models\Other\CiuuCodes;
use App\Models\Other\Company;
use Illuminate\Support\Facades\Log;
use App\Models\Other\LegalDocumentsType;
use App\Models\Other\CiuuCodeCompany;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function getEconomicActivity(Request $request)
    {
        $typeCompany = EconomicActivity::select('activity_name','id')
        ->get();
        return response()->json($typeCompany, 200);
    }

    public function getCodesOfActivity(Request $request)
    {
        // Extraer el activity_id del request de forma segura
        $activityId = $request->input('activity_id');

        // Validamos que se haya enviado el parámetro activity_id
        if (!$activityId) {
            return response()->json(['error' => 'No se proporcionó activity_id'], 400);
        }

        // Realizar la consulta usando el activity_id extraído
        $codes = CiuuCodes::select('class', 'description')
        ->where('economic_activity_id', $activityId)
        ->get();

        return response()->json($codes, 200);
    }

    public function createCompany(Request $request)
    {
        // Mapeo de tipos de empresa a sus IDs
        $companyTypesMap = [
            'SOCIEDAD POR ACCIONES SIMPLIFICADA' => 1,
            'SOCIEDAD ANÓNIMA' => 2,
            'SOCIEDAD DE RESPONSABILIDAD LIMITADA' => 3,
            'SOCIEDAD COLECTIVA' => 4,
            'SOCIEDAD EN COMANDITA SIMPLE' => 5,
            'SOCIEDAD EN COMANDITA POR ACCIONES' => 6,
            'SOCIEDADES DE ECONOMÍA MIXTA' => 7,
            'EMPRESAS DE BENEFICIO E INTERÉS COLECTIVO' => 8,
            'EMPRESA UNIPERSONAL' => 9,
            'EMPRESA INDIVIDUAL DE RESPONSABILIDAD LIMITADA' => 10,
        ];

        $companyTypeName = $request->input('company_type');
        $companyTypeId = $companyTypesMap[$companyTypeName] ?? null;

        if (!$companyTypeId) {
            return response()->json(['error' => 'Tipo de empresa no válido'], 400);
        }

        $data = [
            'name' => $request->input('name'),
            'legal_name' => $request->input('legal_name'),
            'id_number' => $request->input('nit'),
            'verification_digit' => $request->input('verification_digit'),
            'company_type_id' => $companyTypeId,
            'legal_representative' => $request->input('legal_representative'),
            'incorporation_date' => $request->input('incorporation_date'),
            'street' => $request->input('street'),
            'exterior_number' => $request->input('exterior_number'),
            'interior_number' => $request->input('interior_number'),
            'neighborhood' => $request->input('neighborhood'),
            'city' => $request->input('city'),
            'municipality' => $request->input('municipality'),
            'department' => $request->input('department'),
            'postal_code' => $request->input('postal_code'),
            'phone' => $request->input('phone'),
            'prefix_phone' => $request->input('prefix_phone'),
            'email' => $request->input('email'),
            'website' => $request->input('website'),
            'user_contact_phone' => $request->input('user_contact_phone'),
            'user_prefix_phone' => $request->input('user_prefix_phone'),
            'user_contact_email' => $request->input('user_contact_email'),
            'registration_number' => $request->input('registration_number'),
        ];


        // Usar transacción para garantizar integridad de datos
        DB::beginTransaction();
        try {
            $company = Company::create($data);

            // Procesar los códigos CIUU
            if ($request->has('economic_activities') && is_array($request->economic_activities)) {
                $economicActivities = $request->economic_activities;

                // Si hay más de 3 códigos, quedarse con los últimos 3
                if (count($economicActivities) > 3) {
                    $economicActivities = array_slice($economicActivities, -3);
                }

                // Buscar los IDs correspondientes en CiuuCodes
                $ciuuCodesIds = CiuuCodes::whereIn('class', $economicActivities)
                    ->pluck('id')
                    ->toArray();

                // Crear las asociaciones en la tabla intermedia
                foreach ($ciuuCodesIds as $ciuuCodeId) {
                    CiuuCodeCompany::create([
                        'company_id' => $company->id,
                        'ciuu_code_id' => $ciuuCodeId
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'company' => $company], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear la empresa: ' . $e->getMessage()], 500);
        }
    }

    public function getCompany(Request $request): JsonResponse
    {
        $companies = Company::with('companyType:id,name')->get();

        $result = $companies->map(function ($company) {
            $data = $company->toArray();
            $data['company_type_name'] = $company->companyType->name ?? null;
            unset($data['company_type_id']);
            return $data;
        });

        return response()->json($result, 200);
    }

    public function updateCompany(Request $request): JsonResponse
    {
        // Buscar la empresa por id_number
        $id_number = $request->input('nit');
        $company = Company::where('id_number', $id_number)->first();
        $existingCompanyTypeId = $company->company_type_id;

        if (!$company) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $data = [
            'name' => $request->input('name'),
            'legal_name' => $request->input('legal_name'),
            'id_number' => $request->input('nit'),
            'verification_digit' => $request->input('verification_digit'),
            'company_type_id' => $request->input('company_type_id', $existingCompanyTypeId),
            'legal_representative' => $request->input('legal_representative'),
            'incorporation_date' => $request->input('incorporation_date'),
            'street' => $request->input('street'),
            'exterior_number' => $request->input('exterior_number'),
            'interior_number' => $request->input('interior_number'),
            'neighborhood' => $request->input('neighborhood'),
            'city' => $request->input('city'),
            'municipality' => $request->input('municipality'),
            'department' => $request->input('department'),
            'postal_code' => $request->input('postal_code'),
            'phone' => $request->input('phone'),
            'prefix_phone' => $request->input('prefix_phone'),
            'email' => $request->input('email'),
            'website' => $request->input('website'),
            'user_contact_phone' => $request->input('user_contact_phone'),
            'user_prefix_phone' => $request->input('user_prefix_phone'),
            'user_contact_email' => $request->input('user_contact_email'),
            'registration_number' => $request->input('registration_number'),
        ];

        try {
            // Actualizar la información de la empresa
            $updated = $company->update($data);

            // Recargar el modelo para asegurar que tenemos los datos actualizados
            $company = $company->fresh();

            return response()->json([
                'success' => true,
                'company' => $company,
                'message' => 'Empresa actualizada correctamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error updating company: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al actualizar la empresa: ' . $e->getMessage()], 500);
        }
    }


}

