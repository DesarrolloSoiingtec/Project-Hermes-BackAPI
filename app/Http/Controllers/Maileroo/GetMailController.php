<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\Maileroo\Email;
use App\Models\Maileroo\Document;
use App\Models\Maileroo\DocumentEmail;
use App\Models\CompanyEmail;
use App\Models\CompanyEmailDomain;
use App\Models\Domain;
use App\Models\Maileroo\Recipient;
use Illuminate\Support\Facades\Auth;
use Exception;

class GetMailController extends Controller
{

public function getEmails(Request $request): JsonResponse
{
    try {
        // Obtener parámetros de la petición
        $emailId = $request->input('email_id', null);
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search', null);
        $dateFrom = $request->input('date_from', null);
        $dateTo = $request->input('date_to', null);
        $onlySent = $request->input('only_sent', false);
        $onlyReceived = $request->input('only_received', false);

        // Verificar usuario autenticado
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Obtener el ID del correo corporativo del usuario
        $companyEmail = CompanyEmail::where('email', $user->email)->first();
        if (!$companyEmail) {
            return response()->json(['error' => 'Company email not found'], 404);
        }

        Log::info("Usuario autenticado:", [
            'user_email' => $user->email,
            'company_email_id' => $companyEmail->id
        ]);

        // Si se solicita un correo específico
        if ($emailId) {
            return $this->getSingleEmail($emailId, $companyEmail->id);
        }

        // Construir query para obtener lista de correos
        $query = Email::query();

        // CORRECCIÓN: Usar CompanyEmail ID para buscar en TO, CC, BCC
        // Usar tanto string como integer para máxima compatibilidad
        $companyIdString = (string)$companyEmail->id;
        $companyIdInt = (int)$companyEmail->id;

        if ($onlySent) {
            // Solo correos enviados por el usuario
            $query->where('from', $companyEmail->id);
            Log::info("Filtro: Solo correos enviados (from = {$companyEmail->id})");

        } elseif ($onlyReceived) {
            // Solo correos recibidos por el usuario
            // Buscar donde el CompanyEmail ID aparezca en TO, CC o BCC
            $query->where(function($q) use ($companyIdInt, $companyIdString) {
                $q->where(function($subQ) use ($companyIdInt, $companyIdString) {
                    // Probar con integer
                    $subQ->whereJsonContains('to', $companyIdInt)
                         ->orWhereJsonContains('cc', $companyIdInt)
                         ->orWhereJsonContains('bcc', $companyIdInt);
                })->orWhere(function($subQ) use ($companyIdString) {
                    // Probar con string
                    $subQ->whereJsonContains('to', $companyIdString)
                         ->orWhereJsonContains('cc', $companyIdString)
                         ->orWhereJsonContains('bcc', $companyIdString);
                })->orWhere(function($subQ) use ($companyIdInt, $companyIdString) {
                    // Fallback: buscar con LIKE para casos edge
                    $subQ->where('to', 'LIKE', "%{$companyIdInt}%")
                         ->orWhere('cc', 'LIKE', "%{$companyIdInt}%")
                         ->orWhere('bcc', 'LIKE', "%{$companyIdInt}%")
                         ->orWhere('to', 'LIKE', "%{$companyIdString}%")
                         ->orWhere('cc', 'LIKE', "%{$companyIdString}%")
                         ->orWhere('bcc', 'LIKE', "%{$companyIdString}%");
                });
            });
            Log::info("Filtro: Solo correos recibidos (to/cc/bcc contiene {$companyEmail->id} como int o string)");

        } else {
            // Todos los correos relacionados con el usuario
            $query->where(function($q) use ($companyEmail, $companyIdInt, $companyIdString) {
                $q->where('from', $companyEmail->id)  // Correos enviados
                  ->orWhere(function($subQ) use ($companyIdInt, $companyIdString) {
                      // Correos recibidos - probar múltiples formatos
                      $subQ->where(function($jsonQ) use ($companyIdInt, $companyIdString) {
                          // JSON contains con integer
                          $jsonQ->whereJsonContains('to', $companyIdInt)
                                ->orWhereJsonContains('cc', $companyIdInt)
                                ->orWhereJsonContains('bcc', $companyIdInt);
                      })->orWhere(function($jsonQ) use ($companyIdString) {
                          // JSON contains con string
                          $jsonQ->whereJsonContains('to', $companyIdString)
                                ->orWhereJsonContains('cc', $companyIdString)
                                ->orWhereJsonContains('bcc', $companyIdString);
                      })->orWhere(function($likeQ) use ($companyIdInt, $companyIdString) {
                          // LIKE como fallback
                          $likeQ->where('to', 'LIKE', "%{$companyIdInt}%")
                                ->orWhere('cc', 'LIKE', "%{$companyIdInt}%")
                                ->orWhere('bcc', 'LIKE', "%{$companyIdInt}%")
                                ->orWhere('to', 'LIKE', "%{$companyIdString}%")
                                ->orWhere('cc', 'LIKE', "%{$companyIdString}%")
                                ->orWhere('bcc', 'LIKE', "%{$companyIdString}%");
                      });
                  });
            });
            Log::info("Filtro: Todos los correos (enviados from={$companyEmail->id} y recibidos to/cc/bcc contiene {$companyEmail->id})");
        }

        // Aplicar filtro de búsqueda
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
            Log::info("Filtro de búsqueda aplicado: {$search}");
        }

        // Aplicar filtro de fechas
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
            Log::info("Filtro fecha desde: {$dateFrom}");
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
            Log::info("Filtro fecha hasta: {$dateTo}");
        }

        // Ordenar por fecha descendente
        $query->orderBy('created_at', 'desc');

        // Log de la query SQL para debugging
        Log::info("Query SQL generada:", [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        // Paginar resultados
        $emails = $query->paginate($perPage, ['*'], 'page', $page);

        Log::info("Resultados encontrados:", [
            'total' => $emails->total(),
            'current_page' => $emails->currentPage(),
            'per_page' => $emails->perPage()
        ]);

        // Formatear los correos para el frontend
        $formattedEmails = [];
        foreach ($emails->items() as $email) {
            $formattedEmails[] = $this->formatEmailForList($email, $companyEmail->id);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'emails' => $formattedEmails,
                'pagination' => [
                    'total' => $emails->total(),
                    'per_page' => $emails->perPage(),
                    'current_page' => $emails->currentPage(),
                    'last_page' => $emails->lastPage(),
                    'from' => $emails->firstItem(),
                    'to' => $emails->lastItem()
                ]
            ]
        ], 200);

    } catch (Exception $e) {
        Log::error('Error en getEmails: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'ok' => false,
            'error' => 'Error obteniendo correos',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener un correo específico con todos sus detalles y archivos
 *
 * @param int $emailId
 * @param int $companyEmailId
 * @return JsonResponse
 */
    private function getSingleEmail($emailId, $companyEmailId): JsonResponse
    {
        try {
            // Buscar el correo
            $email = Email::find($emailId);

            if (!$email) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Email not found'
                ], 404);
            }

            // Verificar que el usuario tenga acceso a este correo
            $user = Auth::user();
            $recipientIds = Recipient::where('email', $user->email)->pluck('id')->toArray();

            $hasAccess = false;
            if ($email->from == $companyEmailId) {
                $hasAccess = true; // Es el remitente
            } else {
                // Verificar si es destinatario
                $toIds = json_decode($email->to, true) ?: [];
                $ccIds = json_decode($email->cc, true) ?: [];
                $bccIds = json_decode($email->bcc, true) ?: [];

                if (array_intersect($recipientIds, $toIds) ||
                    array_intersect($recipientIds, $ccIds) ||
                    array_intersect($recipientIds, $bccIds)) {
                    $hasAccess = true;
                }
            }

            if (!$hasAccess) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Access denied to this email'
                ], 403);
            }

            // Obtener información del remitente
            $fromEmail = CompanyEmail::find($email->from);

            // Obtener destinatarios TO
            $toIds = json_decode($email->to, true) ?: [];
            $toRecipients = Recipient::whereIn('id', $toIds)->get();
            $toEmails = [];
            foreach ($toRecipients as $recipient) {
                $toEmails[] = [
                    'address' => $recipient->email,
                    'display_name' => $recipient->name ?? null
                ];
            }

            // Obtener destinatarios CC
            $ccIds = json_decode($email->cc, true) ?: [];
            $ccRecipients = Recipient::whereIn('id', $ccIds)->get();
            $ccEmails = [];
            foreach ($ccRecipients as $recipient) {
                $ccEmails[] = [
                    'address' => $recipient->email,
                    'display_name' => $recipient->name ?? null
                ];
            }

            // Obtener destinatarios BCC
            $bccIds = json_decode($email->bcc, true) ?: [];
            $bccRecipients = Recipient::whereIn('id', $bccIds)->get();
            $bccEmails = [];
            foreach ($bccRecipients as $recipient) {
                $bccEmails[] = [
                    'address' => $recipient->email,
                    'display_name' => $recipient->name ?? null
                ];
            }

            // Obtener archivos adjuntos
            $attachments = $this->getEmailAttachments($email->id);

            // Renderizar el contenido HTML con la plantilla
            $htmlContent = view('emails.plantillaDefault', [
                'content' => $email->content,
                'subject' => $email->subject,
                'fromAddress' => $fromEmail->email ?? '',
                'fromName' => $fromEmail->name ?? config('mail.from.name'),
                'appName' => config('app.name'),
            ])->render();

            // Construir respuesta completa del correo
            $emailData = [
                'id' => $email->id,
                'from' => [
                    'address' => $fromEmail->email ?? '',
                    'display_name' => $fromEmail->name ?? config('mail.from.name')
                ],
                'to' => $toEmails,
                'cc' => $ccEmails,
                'bcc' => $bccEmails,
                'subject' => $email->subject,
                'content' => $email->content,          // Contenido plano original
                'html_content' => $htmlContent,        // Contenido HTML renderizado
                'attachments' => $attachments,
                'priority' => $email->priority ?? 'normal',
                'read_receipt' => $email->read_receipt ?? false,
                'created_at' => $email->created_at->toIso8601String(),
                'scheduled_time' => $email->scheduled_time ? $email->scheduled_time->toIso8601String() : null,
                'is_sent' => $email->from == $companyEmailId,
                'is_received' => !($email->from == $companyEmailId),
                'domain_id' => $email->domain_id
            ];

            return response()->json([
                'ok' => true,
                'data' => $emailData
            ], 200);

        } catch (Exception $e) {
            Log::error('Error en getSingleEmail: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'Error obteniendo el correo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener archivos adjuntos de un correo
     *
     * @param int $emailId
     * @return array
     */
    private function getEmailAttachments($emailId): array
    {
        $attachments = [];

        try {
            // Obtener relaciones documento-email
            $documentEmails = DocumentEmail::where('emails_id', $emailId)->get();

            foreach ($documentEmails as $docEmail) {
                // Obtener información del documento
                $document = Document::find($docEmail->documents_id);

                if ($document) {
                    // Construir la ruta completa del archivo
                    $filePath = ltrim($document->path, '/');
                    $fullPath = storage_path('app/' . $filePath);

                    // Verificar si el archivo existe
                    $fileExists = Storage::exists($filePath);

                    // Obtener el nombre original del archivo
                    $fileName = basename($document->path);
                    $fileExtension = ltrim($document->type, '.');

                    // Si el archivo existe, obtener su contenido en base64
                    $fileContent = null;
                    $fileUrl = null;

                    if ($fileExists) {
                        // Opción 1: Enviar contenido en base64 (para archivos pequeños)
                        if ($document->size <= 10) { // Si es menor a 10MB
                            $fileContent = base64_encode(Storage::get($filePath));
                        }

                        // Opción 2: Generar URL temporal para descarga
                        $fileUrl = Storage::url($filePath);
                    }

                    $attachments[] = [
                        'id' => $document->id,
                        'name' => $fileName,
                        'filename' => $fileName,
                        'size' => $document->size, // En MB
                        'size_bytes' => $document->size * 1024 * 1024, // Convertir a bytes
                        'type' => 'application/octet-stream', // Tipo MIME genérico
                        'extension' => $fileExtension,
                        'path' => $document->path,
                        'exists' => $fileExists,
                        'content' => $fileContent, // Base64 content (solo para archivos pequeños)
                        'url' => $fileUrl, // URL para descarga
                        'fieldName' => 'file_' . count($attachments), // Para compatibilidad con el frontend
                        'fromLibrary' => true // Indica que viene del storage
                    ];
                }
            }

        } catch (Exception $e) {
            Log::error('Error obteniendo adjuntos del correo ' . $emailId . ': ' . $e->getMessage());
        }

        return $attachments;
    }

    /**
     * Formatear correo para la lista (versión resumida)
     *
     * @param Email $email
     * @param int $companyEmailId
     * @return array
     */
    private function formatEmailForList($email, $companyEmailId): array
    {
        // Obtener información básica del remitente
        $fromEmail = CompanyEmail::find($email->from);

        // Obtener primer destinatario TO para mostrar en la lista
        $toIds = json_decode($email->to, true) ?: [];
        $firstToRecipient = null;
        if (!empty($toIds)) {
            $recipient = Recipient::find($toIds[0]);
            if ($recipient) {
                $firstToRecipient = $recipient->email;
            }
        }

        // Contar archivos adjuntos
        $attachmentCount = DocumentEmail::where('emails_id', $email->id)->count();

        // Extraer preview del contenido (primeros 100 caracteres)
        $contentPreview = strip_tags($email->content);
        $contentPreview = substr($contentPreview, 0, 100);
        if (strlen($email->content) > 100) {
            $contentPreview .= '...';
        }

        return [
            'id' => $email->id,
            'from' => $fromEmail->email ?? '',
            'from_name' => $fromEmail->name ?? '',
            'to' => $firstToRecipient,
            'to_count' => count($toIds),
            'subject' => $email->subject,
            'preview' => $contentPreview,
            'has_attachments' => $attachmentCount > 0,
            'attachment_count' => $attachmentCount,
            'created_at' => $email->created_at->toIso8601String(),
            'date_human' => $email->created_at->diffForHumans(),
            'is_sent' => $email->from == $companyEmailId,
            'is_received' => !($email->from == $companyEmailId)
        ];
    }

    /**
     * Descargar un archivo adjunto específico
     *
     * @param Request $request
     * @return mixed
     */
    public function downloadAttachment(Request $request)
    {
        try {
            $documentId = $request->input('document_id');
            $emailId = $request->input('email_id');

            if (!$documentId || !$emailId) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Missing parameters'
                ], 400);
            }

            // Verificar que el usuario tenga acceso al correo
            $user = Auth::user();
            $companyEmail = CompanyEmail::where('email', $user->email)->first();
            $email = Email::find($emailId);

            if (!$email || !$companyEmail) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Email not found'
                ], 404);
            }

            // Verificar acceso
            $recipientIds = Recipient::where('email', $user->email)->pluck('id')->toArray();
            $hasAccess = false;

            if ($email->from == $companyEmailId) {
                $hasAccess = true; // Es el remitente
            } else {
                // Verificar si es destinatario usando CompanyEmail ID
                $toIds = json_decode($email->to, true) ?: [];
                $ccIds = json_decode($email->cc, true) ?: [];
                $bccIds = json_decode($email->bcc, true) ?: [];

                if (in_array($companyEmailId, $toIds) ||
                    in_array($companyEmailId, $ccIds) ||
                    in_array($companyEmailId, $bccIds)) {
                    $hasAccess = true;
                }
            }

            if (!$hasAccess) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Access denied'
                ], 403);
            }

            // Verificar que el documento pertenece al correo
            $documentEmail = DocumentEmail::where('emails_id', $emailId)
                ->where('documents_id', $documentId)
                ->first();

            if (!$documentEmail) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Document not found in this email'
                ], 404);
            }

            // Obtener el documento
            $document = Document::find($documentId);
            if (!$document) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Document not found'
                ], 404);
            }

            // Obtener el archivo
            $filePath = ltrim($document->path, '/');

            if (!Storage::exists($filePath)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'File not found in storage'
                ], 404);
            }

            // Retornar el archivo para descarga
            return Storage::download($filePath, basename($document->path));

        } catch (Exception $e) {
            Log::error('Error descargando adjunto: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'Error downloading attachment',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getEmailOnly(Request $request, $emailId): JsonResponse
    {
        try {
            // 1. Buscar el correo del usuario autenticado
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // 2. Buscar el ID del correo de usuario autenticado
            $companyEmail = CompanyEmail::where('email', $user->email)->first();
            if (!$companyEmail) {
                return response()->json(['error' => 'Company email not found'], 404);
            }
            $companyEmailId = $companyEmail->id;

            // 3. Buscar el Email por ID
            $email = Email::find($emailId);
            if (!$email) {
                return response()->json(['error' => 'Email not found'], 404);
            }

            // 4. Verificar relación del usuario con el email (to/from)
            $userRelation = null;
            $toIds = json_decode($email->to, true) ?: [];
            $fromInfo = null;
            $toInfo = [];

            // 5. Determinar la relación y obtener información adicional
            if ($email->from == $companyEmailId) {
                // Usuario es el remitente
                $userRelation = "from: {$user->email}";

                // 7. Buscar destinatarios en Recipient
                if (!empty($toIds)) {
                    $toInfo = Recipient::whereIn('id', $toIds)->get();
                }
            }
            else if (in_array($companyEmailId, $toIds) || in_array((string)$companyEmailId, $toIds)) {
                // Usuario es destinatario
                $userRelation = "to: {$user->email}";

                // 6. Buscar remitente en Recipient
                if ($email->from) {
                    $fromInfo = Recipient::find($email->from);
                }
            }
            else {
                return response()->json(['error' => 'User not authorized to view this email'], 403);
            }

            // Obtener documentos relacionados con el email
            $documentEmails = DocumentEmail::where('emails_id', $emailId)->get();
            $documentsIds = $documentEmails->pluck('documents_id')->toArray();
            $documents = Document::whereIn('id', $documentsIds)->get();

            // Retornar toda la información requerida
            return response()->json([
                'ok' => true,
                'data' => [
                    'email' => $email,
                    'from' => $fromInfo,
                    'to' => $toInfo,
                    'documents' => $documents,
                    'documents_count' => count($documents),
                    'user_relation' => $userRelation
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getEmailOnly', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

}
