<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Other\LegalDocumentsType;

class LegalDocumentController extends Controller
{
    public function getDocuments(){
        $documents = LegalDocumentsType::select('id', 'name', 'code')
        ->where('for_company', false)
        ->get();

        return response()->json($documents, 200);
    }

    public function getDocumentsCompany(){
        $Company = LegalDocumentsType::select('id','name')
        ->where('for_company', true)
        ->get();

        return response()->json($Company, 200);
    }

    public function getDocumentsCompanyApb(){
        $Company = LegalDocumentsType::select('id','code')
            ->where('for_company', true)
            ->get();

        return response()->json($Company, 200);
    }

}
