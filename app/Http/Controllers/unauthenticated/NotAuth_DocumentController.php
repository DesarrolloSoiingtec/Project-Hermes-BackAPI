<?php

namespace App\Http\Controllers\unauthenticated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Other\LegalDocumentsType;
use Illuminate\Http\JsonResponse;

class NotAuth_DocumentController extends Controller
{
        /**
     * Obtener tipos de documentos legales públicos (no para empresas).
     *
     * Esta función expone únicamente los tipos de documentos legales marcados como
     * `for_company = false`, pensados para ser consumidos desde la vista pública.
     *
     * Seguridad aplicada:
     * - No requiere autenticación.
     * - Se recomienda combinar esta ruta con un middleware de `throttle` para evitar abusos.
     * - Se hace logging básico para trazabilidad.
     *
     * @return JsonResponse Listado de documentos disponibles públicamente.
     */
    public function getDocuments_NotAuth(): JsonResponse
    {
        // [Trazabilidad] Logueamos acceso a esta ruta pública con IP
        Log::info('Consulta pública de tipos de documentos legales desde IP: ' . request()->ip());

        // [Consulta segura] Solo se expone 'id' y 'name' donde for_company = false
        $documents = LegalDocumentsType::select('id', 'name')
            ->where('for_person', true)
            ->get();

        // [Respuesta] Lista simple de documentos legales
        return response()->json($documents, 200);
    }
}
