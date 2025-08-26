<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\Maileroo\Recipient;
use App\Models\Maileroo\Email;
use App\Models\Maileroo\Document;
use App\Models\Maileroo\DocumentEmail;
use App\Models\CompanyEmail;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyEmailDomain;
use Illuminate\Support\Str;
use Exception;

class SendMailController extends Controller
{
    /**
     * Rutas de almacenamiento donde buscar archivos adjuntos
     */
    private const STORAGE_PATHS = [
        'public/fileCourse/',
        'public/users/',
        'public/persons/',
        'public/signature/',
        'public/helpVideo/',
        'public/helpVideos/',
        'public/'
    ];

    public function sendMail(Request $request): JsonResponse
    {
        log::info("request total", $request->all());

        // AGREGAR DEBUG PARA VER QUÉ ARCHIVOS LLEGAN
        Log::info('=== DEBUG ARCHIVOS ===');
        $allFiles = $request->allFiles();
        foreach ($allFiles as $key => $file) {
            if ($file) {
                Log::info("Archivo recibido: {$key}", [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize(),
                    'tipo' => $file->getMimeType()
                ]);
            }
        }
        Log::info('=== FIN DEBUG ===');

        // Decodificar el payload JSON - usando la lógica del código antiguo
        $rawPayload = $request->input('payload', null);
        $decodedPayload = null;

        if ($rawPayload && is_string($rawPayload)) {
            $decodedPayload = json_decode($rawPayload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedPayload = null;
            }
        }

        if (!$decodedPayload) {
            $decodedPayload = $request->all();
            if (isset($decodedPayload['payload']) && is_string($decodedPayload['payload'])) {
                $try = json_decode($decodedPayload['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decodedPayload = $try;
                }
            }
        }

        // Extraer datos usando data_get para mayor seguridad
        $to = data_get($decodedPayload, 'to', []);
        $cc = data_get($decodedPayload, 'cc', []);
        $bcc = data_get($decodedPayload, 'bcc', []);
        $subject = data_get($decodedPayload, 'subject', '');
        $content = data_get($decodedPayload, 'content', data_get($decodedPayload, 'plain', ''));
        $attachmentsList = data_get($decodedPayload, 'attachments', []);
        $priority = data_get($decodedPayload, 'priority', 'normal');
        $readReceipt = data_get($decodedPayload, 'readReceipt', false);
        $fromAddress = data_get($decodedPayload, 'from.address', config('mail.from.address'));
        $fromName = data_get($decodedPayload, 'from.display_name', config('mail.from.name'));

        Log::info("datos del sendMail: ", $decodedPayload ?? []);

        // Verificar usuario autenticado
        $user = Auth::user();
        if (!$user) {
            Log::warning('sendMail llamado sin usuario autenticado', ['payload' => $decodedPayload]);
            return response()->json(['error' => 'Unauthenticated. Por favor inicie sesión.'], 401);
        }

        // Procesar el correo corporativo
        $fromResult = $this->processFrom($user->email);

        // Procesar emails de destinatarios
        $emailsResult = $this->processTo($to);
        $ccResult = $this->processTo($cc);
        $bccResult = $this->processTo($bcc);

        // Extraer ids y emails
        $ids = $emailsResult['ids'] ?? [];
        $recipientEmails = $emailsResult['emails'] ?? [];
        $ccIds = $ccResult['ids'] ?? [];
        $ccEmails = $ccResult['emails'] ?? [];
        $bccIds = $bccResult['ids'] ?? [];
        $bccEmails = $bccResult['emails'] ?? [];

        // Obtener CompanyEmail ID
        $companyEmail = CompanyEmail::firstOrCreate(['email' => $user->email]);
        $mailUsername = $companyEmail->id;

        log::info("ID del correo corporativo (CompanyEmail): ", ['id' => $mailUsername, 'email' => $user->email]);

        $domainIds = CompanyEmailDomain::where('company_email_id', $mailUsername)
            ->pluck('domain_id')
            ->first();

        log::info("ID del dominio corporativo: ", ['id' => $domainIds, 'email' => $user->email]);

        // Procesar archivos adjuntos para almacenamiento local (guardar en storage)
        $processedAttachmentsForStorage = $this->processAttachmentsForStorage($request, $attachmentsList);
        Log::info("Archivos adjuntos procesados para storage:", $processedAttachmentsForStorage);

        // Guardar información del correo en la base de datos
        $emailSave = new Email();
        $emailSave->from = $fromResult['id'] ?? null;
        $emailSave->domain_id = $domainIds;
        $emailSave->to = json_encode($ids);
        $emailSave->cc = json_encode($ccIds);
        $emailSave->bcc = json_encode($bccIds);
        $emailSave->subject = $subject;
        $emailSave->content = $content;
        $emailSave->scheduled_time = null;
        $emailSave->created_at = now();
        $emailSave->save();

        // Guardar documentos adjuntos en la base de datos
        if (!empty($processedAttachmentsForStorage)) {
            foreach ($processedAttachmentsForStorage as $attachment) {
                try {
                    // Guardar en tabla Document
                    $saveDocument = new Document();
                    $saveDocument->size = round($attachment['size'] / 1024 / 1024, 2); // Tamaño en MB
                    $saveDocument->type = '.' . pathinfo($attachment['original_name'], PATHINFO_EXTENSION);
                    $saveDocument->path = '/' . $attachment['path'];
                    $saveDocument->save();

                    Log::info("Archivo guardado en BD: {$attachment['original_name']} (ID: {$saveDocument->id})");

                    // Crear relación DocumentEmail
                    $emailForDocument = new DocumentEmail();
                    $emailForDocument->emails_id = $emailSave->id;
                    $emailForDocument->documents_id = $saveDocument->id;
                    $emailForDocument->save();

                    Log::info("Relación documento-correo guardada");

                } catch (Exception $e) {
                    Log::error("Error guardando archivo en BD {$attachment['original_name']}: " . $e->getMessage());
                }
            }
        }

        // CAMBIO CRÍTICO: Renderizar el contenido HTML con la plantilla ANTES de enviar
        $htmlContent = view('emails.plantillaDefault', [
            'content' => $content,
            'subject' => $subject,
            'fromAddress' => $user->email,  // Usar el email del usuario
            'fromName' => $fromName,
            'appName' => config('app.name'),
        ])->render();

        // Enviar email a través de Maileroo API o SMTP
        $apiUrl = env('MAILER_API_URL');
        if ($apiUrl) {
            try {
                // CAMBIO IMPORTANTE: Usar el método del código antiguo para procesar adjuntos
                $processedAttachments = $this->processAttachmentsForApi($request, $attachmentsList);

                // Construir payload para API
                $apiPayload = [
                    'to' => $to,
                    'cc' => $cc,
                    'bcc' => $bcc,
                    'subject' => $subject,
                    'content' => $htmlContent, // CAMBIO: Usar el HTML renderizado, no el content plano
                    'from' => [
                        'address' => $user->email,
                        'display_name' => $fromName
                    ]
                ];

                // Agregar campos opcionales
                if ($priority !== null) {
                    $apiPayload['priority'] = $priority;
                }
                if ($readReceipt !== null) {
                    $apiPayload['readReceipt'] = $readReceipt;
                }
                if (!empty($processedAttachments)) {
                    $apiPayload['attachments'] = $processedAttachments;
                }

                Log::info('Enviando a Maileroo API', [
                    'url' => $apiUrl,
                    'payload_keys' => array_keys($apiPayload),
                    'attachments_count' => count($processedAttachments),
                    'has_html' => !empty($htmlContent)
                ]);

                $response = Http::withBasicAuth(env('MAIL_USERNAME'), env('MAIL_PASSWORD'))
                    ->acceptJson()
                    ->timeout(60)
                    ->post($apiUrl, $apiPayload);

                if ($response->successful()) {
                    return response()->json([
                        'ok' => true,
                        'message' => 'Email enviado exitosamente por Maileroo API',
                        'response' => $response->json(),
                        'attachments_sent' => count($processedAttachments)
                    ], 200);
                }

                Log::warning('Maileroo API returned error, falling back to SMTP', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Si falla la API, intentar con SMTP
                return $this->sendBySMTP($request, $user, $to, $cc, $bcc, $subject, $content, $attachmentsList, $fromAddress, $fromName);

            } catch (Exception $e) {
                Log::error('Error enviando a Maileroo API, intentando SMTP', [
                    'exception' => $e->getMessage()
                ]);

                // Si hay error con la API, intentar con SMTP
                return $this->sendBySMTP($request, $user, $to, $cc, $bcc, $subject, $content, $attachmentsList, $fromAddress, $fromName);
            }
        }

        // Si no hay API configurada, usar SMTP directamente
        return $this->sendBySMTP($request, $user, $to, $cc, $bcc, $subject, $content, $attachmentsList, $fromAddress, $fromName);
    }

    /**
     * Procesar archivos adjuntos para almacenamiento local
     * (Este método se mantiene igual, solo guarda en storage)
     */
    private function processAttachmentsForStorage(Request $request, array $attachments): array
    {
        $processedFiles = [];

        // Primero intentar con fieldName
        foreach ($attachments as $attachment) {
            $fieldName = $attachment['fieldName'] ?? null;
            $originalName = $attachment['name'] ?? 'unknown';

            if ($fieldName && $request->hasFile($fieldName)) {
                $file = $request->file($fieldName);

                if ($file && $file->isValid()) {
                    try {
                        // Generar UUID para el nombre del archivo
                        $uuid = Str::uuid();
                        $extension = $file->getClientOriginalExtension();
                        $newFileName = $uuid . '.' . $extension;

                        // Guardar archivo en la carpeta 'sendEmail'
                        $path = Storage::putFileAs('sendEmail', $file, $newFileName);

                        $processedFiles[] = [
                            'original_name' => $originalName,
                            'stored_name' => $newFileName,
                            'path' => $path,
                            'size' => $file->getSize(),
                            'mime_type' => $file->getClientMimeType(),
                            'uuid' => $uuid
                        ];

                        Log::info("Archivo procesado para storage: {$originalName} -> {$newFileName}");

                    } catch (Exception $e) {
                        Log::error("Error procesando archivo adjunto {$originalName}: " . $e->getMessage());
                    }
                }
            }
        }

        // Si no se encontraron archivos por fieldName, buscar file_0, file_1, etc.
        if (empty($processedFiles)) {
            $allFiles = $request->allFiles();
            foreach ($allFiles as $key => $file) {
                if (strpos($key, 'file_') === 0 && $file && $file->isValid()) {
                    try {
                        $uuid = Str::uuid();
                        $extension = $file->getClientOriginalExtension();
                        $newFileName = $uuid . '.' . $extension;
                        $path = Storage::putFileAs('sendEmail', $file, $newFileName);

                        $processedFiles[] = [
                            'original_name' => $file->getClientOriginalName(),
                            'stored_name' => $newFileName,
                            'path' => $path,
                            'size' => $file->getSize(),
                            'mime_type' => $file->getClientMimeType(),
                            'uuid' => $uuid
                        ];

                        Log::info("Archivo procesado para storage (file_X): " . $file->getClientOriginalName());
                    } catch (Exception $e) {
                        Log::error("Error procesando archivo {$key}: " . $e->getMessage());
                    }
                }
            }
        }

        return $processedFiles;
    }

    /**
     * Procesar archivos adjuntos para API (formato base64)
     * MÉTODO TOMADO DEL CÓDIGO ANTIGUO - COMPLETO
     */
    private function processAttachmentsForApi(Request $request, array $attachmentsList): array
    {
        $processedAttachments = [];

        foreach ($attachmentsList as $attachment) {
            $result = $this->processSingleAttachment($request, $attachment);
            if ($result) {
                $processedAttachments[] = $result;
            }
        }

        return $processedAttachments;
    }

    /**
     * Procesar un único adjunto - MÉTODO DEL CÓDIGO ANTIGUO
     */
    private function processSingleAttachment(Request $request, array $attachment): ?array
    {
        $attachmentName = data_get($attachment, 'name');
        $fromLibrary = data_get($attachment, 'fromLibrary', false);
        $fieldName = data_get($attachment, 'fieldName');

        // Caso 1: Archivo subido con fieldName específico
        if (!$fromLibrary && $fieldName && $request->hasFile($fieldName)) {
            return $this->processUploadedFile($request->file($fieldName));
        }

        // Caso 2: Buscar en campo 'files' genérico
        if (!$fromLibrary && !$fieldName && $request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file->isValid() && $file->getClientOriginalName() === $attachmentName) {
                    return $this->processUploadedFile($file);
                }
            }
        }

        // Caso 3: Buscar archivos file_0, file_1, etc.
        if (!$fromLibrary) {
            $allFiles = $request->allFiles();
            foreach ($allFiles as $key => $file) {
                if (strpos($key, 'file_') === 0 && $file && $file->isValid()) {
                    if ($file->getClientOriginalName() === $attachmentName || $attachmentName === 'unknown') {
                        return $this->processUploadedFile($file);
                    }
                }
            }
        }

        // Caso 4: Archivo ya en base64
        if (isset($attachment['content']) && isset($attachment['filename'])) {
            return [
                'filename' => $attachment['filename'],
                'content' => $attachment['content'],
                'content_type' => data_get($attachment, 'type', 'application/octet-stream')
            ];
        }

        // Caso 5: Archivo desde storage
        if ($fromLibrary || ($attachmentName && !isset($attachment['content']))) {
            return $this->processStorageFile($attachmentName, $attachment);
        }

        Log::warning('Attachment sin archivo válido', [
            'attachment' => $attachment,
            'has_files_field' => $request->hasFile('files'),
            'has_content' => isset($attachment['content']),
            'from_library' => $fromLibrary
        ]);

        return null;
    }

    /**
     * Procesar archivo subido a base64
     */
    private function processUploadedFile($file): ?array
    {
        if (!$file->isValid()) {
            return null;
        }

        $fileContent = file_get_contents($file->getRealPath());
        if ($fileContent === false) {
            return null;
        }

        return [
            'filename' => $file->getClientOriginalName(),
            'content' => base64_encode($fileContent),
            'content_type' => $file->getMimeType()
        ];
    }

    /**
     * Buscar y procesar archivo desde storage
     */
    private function processStorageFile(string $attachmentName, array $attachment): ?array
    {
        foreach (self::STORAGE_PATHS as $basePath) {
            $storagePath = $basePath . $attachmentName;

            if (Storage::exists($storagePath)) {
                try {
                    $fileContent = Storage::get($storagePath);
                    return [
                        'filename' => $attachmentName,
                        'content' => base64_encode($fileContent),
                        'content_type' => data_get($attachment, 'type', 'application/octet-stream')
                    ];
                } catch (Exception $e) {
                    Log::error('Error leyendo archivo desde storage', [
                        'name' => $attachmentName,
                        'storage_path' => $storagePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Procesar el correo corporativo
     */
    private function processFrom($email): array
    {
        if (is_array($email)) {
            $email = is_string($email[0]) ? $email[0] : $email[0]['address'];
        }
        $record = CompanyEmail::updateOrCreate(
            ['email' => $email],
            ['email' => $email]
        );
        return [
            'id' => $record->id,
            'email' => $record->email,
        ];
    }

    /**
     * Procesar los correos destinatarios
     */
    private function processTo(array $recipients): array
    {
        // Normalizar entrada
        $emailsToProcess = [];
        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $emailsToProcess[] = $recipient;
            } elseif (is_array($recipient) && isset($recipient['address'])) {
                $emailsToProcess[] = $recipient['address'];
            }
        }

        // Si no hay emails, retornar array vacío
        if (empty($emailsToProcess)) {
            return [
                'ids' => [],
                'emails' => []
            ];
        }

        // Eliminar duplicados
        $emailsToProcess = array_unique($emailsToProcess);

        // Usar updateOrCreate para cada email
        $allRecords = collect();
        foreach ($emailsToProcess as $email) {
            $record = Recipient::updateOrCreate(
                ['email' => $email],
                ['email' => $email]
            );
            $allRecords->push($record);
        }

        $idsEmail = $allRecords->pluck('id')->values()->toArray();
        $emails = $allRecords->pluck('email')->values()->toArray();

        return [
            'ids' => $idsEmail,
            'emails' => $emails,
        ];
    }

    /**
     * Enviar correo por SMTP como fallback
     */
    private function sendBySMTP(
        Request $request,
        $user,
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $content,
        array $attachmentsList,
        string $fromAddress,
        string $fromName
    ): JsonResponse {
        try {
            // Configurar SMTP dinámicamente
            config([
                'mail.mailers.smtp.host' => env('MAIL_HOST'),
                'mail.mailers.smtp.port' => env('MAIL_PORT'),
                'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION'),
                'mail.mailers.smtp.username' => $user->email,
                'mail.mailers.smtp.password' => $user->password_mail,
                'mail.from.address' => $user->email,
                'mail.from.name' => env('MAIL_FROM_NAME'),
            ]);

            // Validar que hay al menos un destinatario
            if (empty($to) && empty($cc) && empty($bcc)) {
                Log::warning('Intento de envío sin destinatarios');
                return response()->json([
                    'ok' => false,
                    'message' => 'No recipients provided (to, cc, or bcc).'
                ], 400);
            }

            // Usar subject con fallback si está vacío
            if (empty($subject)) {
                $subject = '(sin asunto)';
            }

            $tempFiles = [];

            // Enviar email usando Mail facade con plantilla
            Mail::send('emails.plantillaDefault', [
                'content' => $content,
                'subject' => $subject,
                'fromAddress' => $fromAddress,
                'fromName' => $fromName,
                'appName' => config('app.name'),
            ], function ($message) use ($fromAddress, $fromName, $subject, $to, $cc, $bcc, $attachmentsList, $request, &$tempFiles) {
                $message->from($fromAddress, $fromName);
                $message->subject($subject);

                // Agregar destinatarios
                $this->addRecipientsToMessage($message, $to, 'to');
                $this->addRecipientsToMessage($message, $cc, 'cc');
                $this->addRecipientsToMessage($message, $bcc, 'bcc');

                // Procesar y adjuntar archivos - USANDO MÉTODO DEL CÓDIGO ANTIGUO
                $this->processAttachmentsForSmtp($message, $attachmentsList, $request, $tempFiles);
            });

            // Limpiar archivos temporales
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            Log::info('Email enviado exitosamente por SMTP');

            return response()->json([
                'ok' => true,
                'message' => 'Email enviado exitosamente por SMTP',
                'recipients_total' => count($to) + count($cc) + count($bcc)
            ], 200);

        } catch (Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (isset($tempFiles)) {
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            }

            Log::error('Error enviando por SMTP', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error sending by SMTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar adjuntos para SMTP - TOMADO DEL CÓDIGO ANTIGUO
     */
    private function processAttachmentsForSmtp($message, array $attachmentsList, Request $request, array &$tempFiles): void
    {
        foreach ($attachmentsList as $attachment) {
            $this->attachFileToMessage($message, $attachment, $request, $tempFiles);
        }
    }

    /**
     * Adjuntar archivo a mensaje - TOMADO DEL CÓDIGO ANTIGUO COMPLETO
     */
    private function attachFileToMessage($message, array $attachment, Request $request, array &$tempFiles): void
    {
        $attachmentName = data_get($attachment, 'name');
        $attachmentType = data_get($attachment, 'type');
        $fromLibrary = data_get($attachment, 'fromLibrary', false);
        $fieldName = data_get($attachment, 'fieldName');

        // Caso 1: Archivo subido con fieldName específico
        if (!$fromLibrary && $fieldName && $request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            if ($file->isValid()) {
                $message->attach($file->getRealPath(), [
                    'as' => $file->getClientOriginalName(),
                    'mime' => $attachmentType ?: $file->getMimeType()
                ]);
                Log::info("Archivo adjuntado por fieldName: " . $file->getClientOriginalName());
            }
            return;
        }

        // Caso 2: Buscar en campo 'files' genérico
        if (!$fromLibrary && !$fieldName && $request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file->isValid() && $file->getClientOriginalName() === $attachmentName) {
                    $message->attach($file->getRealPath(), [
                        'as' => $file->getClientOriginalName(),
                        'mime' => $attachmentType ?: $file->getMimeType()
                    ]);
                    Log::info("Archivo adjuntado desde files: " . $file->getClientOriginalName());
                    return;
                }
            }
        }

        // Caso 3: Buscar archivos file_0, file_1, etc.
        if (!$fromLibrary) {
            $allFiles = $request->allFiles();
            foreach ($allFiles as $key => $file) {
                if (strpos($key, 'file_') === 0 && $file && $file->isValid()) {
                    if ($file->getClientOriginalName() === $attachmentName || $attachmentName === 'unknown') {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $attachmentType ?: $file->getMimeType()
                        ]);
                        Log::info("Archivo adjuntado desde {$key}: " . $file->getClientOriginalName());
                        return;
                    }
                }
            }
        }

        // Caso 4: Archivo en base64
        if (isset($attachment['content']) && isset($attachment['filename'])) {
            $this->attachBase64File($message, $attachment, $attachmentType, $tempFiles);
            return;
        }

        // Caso 5: Archivo desde storage
        if ($fromLibrary || ($attachmentName && !isset($attachment['content']))) {
            $this->attachStorageFile($message, $attachmentName, $attachmentType, $tempFiles);
        }
    }

    /**
     * Adjuntar archivo base64
     */
    private function attachBase64File($message, array $attachment, ?string $attachmentType, array &$tempFiles): void
    {
        try {
            $content = base64_decode($attachment['content']);
            $tempPath = tempnam(sys_get_temp_dir(), 'email_attachment_');
            file_put_contents($tempPath, $content);

            $tempFiles[] = $tempPath;

            $message->attach($tempPath, [
                'as' => $attachment['filename'],
                'mime' => $attachmentType ?: 'application/octet-stream'
            ]);

            Log::info("Archivo base64 adjuntado: " . $attachment['filename']);
        } catch (Exception $e) {
            Log::error('Error creando archivo temporal', [
                'filename' => $attachment['filename'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Adjuntar archivo desde storage
     */
    private function attachStorageFile($message, string $attachmentName, ?string $attachmentType, array &$tempFiles): void
    {
        foreach (self::STORAGE_PATHS as $basePath) {
            $storagePath = $basePath . $attachmentName;

            if (Storage::exists($storagePath)) {
                try {
                    $content = Storage::get($storagePath);
                    $tempPath = tempnam(sys_get_temp_dir(), 'email_attachment_');
                    file_put_contents($tempPath, $content);

                    $tempFiles[] = $tempPath;

                    $message->attach($tempPath, [
                        'as' => $attachmentName,
                        'mime' => $attachmentType ?: 'application/octet-stream'
                    ]);

                    Log::info("Archivo desde storage adjuntado: " . $attachmentName);
                    return;
                } catch (Exception $e) {
                    Log::error('Error procesando archivo desde storage para SMTP', [
                        'name' => $attachmentName,
                        'storage_path' => $storagePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Agregar destinatarios al mensaje
     */
    private function addRecipientsToMessage($message, array $recipients, string $type): void
    {
        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $message->$type($recipient);
            } elseif (is_array($recipient) || is_object($recipient)) {
                $address = data_get($recipient, 'address');
                $name = data_get($recipient, 'display_name', null);
                if ($address) {
                    $message->$type($address, $name);
                }
            }
        }
    }
}
