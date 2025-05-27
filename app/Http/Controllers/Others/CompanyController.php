<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Other\EconomicActivity;
use App\Models\Other\CiuuCodes;
use Illuminate\Support\Facades\Log;


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

    public function createCompany(Request $request){
        Log::info('createCompany: ', $request->all());
    }




}
