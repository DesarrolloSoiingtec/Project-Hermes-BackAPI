<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Maileroo\Message;
use App\Models\Maileroo\Recipient;
use App\Models\Maileroo\Email;
use App\Models\Maileroo\Document;
use App\Models\Maileroo\DocumentEmail;
use App\Models\Maileroo\Event;
use App\Models\Maileroo\EventMetadata;
use App\Models\CompanyEmail;
use App\Models\CompanyEmailDomain as Domain;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MailerooController extends Controller
{

    /**
    * Router principal que decide si es un evento o un correo entrante
    */
    public function handleIncomingRequest(Request $request): JsonResponse
    {
        try {
            Log::info("=== REQUEST ENTRANTE - ROUTER ===");
            $requestData = $request->all();

            // Identificar tipo de request
            if ($this->isEventRequest($requestData)) {
                Log::info("Detectado como EVENTO - Redirigiendo a newEvent()");
                return $this->newEvent($request);
            } elseif ($this->isEmailRequest($requestData)) {
                Log::info("Detectado como CORREO ENTRANTE - Redirigiendo a receiveEmail()");
                return $this->receiveEmail($request);
            } else {
                Log::warning("Tipo de request no reconocido", $requestData);
                return response()->json([
                    'ok' => false,
                    'message' => 'Tipo de request no reconocido'
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('Error en router principal: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Error procesando request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detectar si es un evento (webhook de estado de email)
     */
    private function isEventRequest(array $data): bool
    {
        // Los eventos tienen estas características:
        $eventFields = ['event_type', 'event_id', 'event_time', 'message_id'];
        $eventFieldsFound = 0;

        foreach ($eventFields as $field) {
            if (array_key_exists($field, $data)) {
                $eventFieldsFound++;
            }
        }

        // Si tiene al menos 3 de los 4 campos de evento, es un evento
        return $eventFieldsFound >= 3;
    }

    /**
     * Detectar si es un correo entrante
     */
    private function isEmailRequest(array $data): bool
    {
        // Los correos entrantes tienen estas características:
        $emailFields = ['recipients', 'body', 'headers', 'envelope_sender'];
        $emailFieldsFound = 0;

        foreach ($emailFields as $field) {
            if (array_key_exists($field, $data)) {
                $emailFieldsFound++;
            }
        }

        // Si tiene al menos 3 de los 4 campos de correo, es un correo
        return $emailFieldsFound >= 3;
    }



    public function newEvent(Request $request)
    {
        try {
            $webhookData = $request->all();

            // Verificar si el evento ya existe para evitar duplicados
            $exists = DB::table('events')
                ->where('external_event_id', $webhookData['event_id'])
                ->exists();

            if ($exists) {
                return response()->json(['success' => true, 'message' => 'Evento ya existe'], 200);
            }

            DB::beginTransaction();

            // 1. Buscar o crear el mensaje en tu tabla 'messages'
            $message = DB::table('messages')
                ->where('message_id', $webhookData['message_id'])
                ->first();

            if (!$message) {
                $messageId = DB::table('messages')->insertGetId([
                    'message_id' => $webhookData['message_id'],
                    'subject' => null,
                    'content' => null,
                    'date_message' => Carbon::createFromTimestamp($webhookData['event_time'])->setTimezone('America/Bogota'),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            } else {
                $messageId = $message->id;
            }

            // 2. Guardar el evento en tu tabla 'events'
            $eventId = DB::table('events')->insertGetId([
                'external_event_id' => $webhookData['event_id'],
                'event_type' => $webhookData['event_type'],
                'timestamp' => Carbon::createFromTimestamp($webhookData['event_time'])->setTimezone('America/Bogota'),
                'inserted_at' => Carbon::parse($webhookData['inserted_at'])->setTimezone('America/Bogota'),
                'reject_reason' => $webhookData['reject_reason'] ?? null,
                'domain' => isset($webhookData['message_id'])
                    ? (explode('@', str_replace(['<', '>'], '', $webhookData['message_id']))[1] ?? null)
                    : null,
                'user_id' => $webhookData['user_id'],
                'message_id' => $messageId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 3. Procesar destinatarios si existen
            $eventData = $webhookData['event_data'] ?? [];
            if (isset($eventData['to']) || isset($eventData['from'])) {
                $this->processRecipients($messageId, $eventData);
            }

            // 4. Guardar metadata si existe (IP, user_agent, etc.)
            if (isset($eventData['ip']) || isset($eventData['user_agent'])) {
                DB::table('event_metadata')->insert([
                    'event_id' => $eventId,
                    'ip' => $eventData['ip'] ?? null,
                    'user_agent' => $eventData['user_agent'] ?? null,
                    'MTA' => $eventData['MTA'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Procesa destinatarios usando TU tabla 'recipients' y 'messages_recipients'
     */
    private function processRecipients($messageId, $eventData)
    {
        $emails = [];

        // Extraer emails del event_data
        if (isset($eventData['to'])) {
            if (is_array($eventData['to'])) {
                $emails = array_merge($emails, $eventData['to']);
            } else {
                $emails[] = $eventData['to'];
            }
        }

        if (isset($eventData['from']) && is_array($eventData['from'])) {
            $emails = array_merge($emails, $eventData['from']);
        }

        foreach ($emails as $emailString) {
            // Limpiar emails (por si vienen separados por comas)
            $cleanEmails = array_map('trim', explode(',', $emailString));

            foreach ($cleanEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Buscar o crear en tu tabla 'recipients'
                    $recipient = DB::table('recipients')->where('email', $email)->first();

                    if (!$recipient) {
                        $recipientId = DB::table('recipients')->insertGetId([
                            'email' => $email,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $recipientId = $recipient->id;
                    }

                    // Asociar en tu tabla 'messages_recipients' (si no existe ya)
                    $exists = DB::table('messages_recipients')
                        ->where('messages_id', $messageId)
                        ->where('recipient_id', $recipientId)
                        ->exists();

                    if (!$exists) {
                        DB::table('messages_recipients')->insert([
                            'messages_id' => $messageId,
                            'recipient_id' => $recipientId
                        ]);
                    }
                }
            }
        }
    }


/**
 * Recibir y almacenar correo entrante
 */
public function receiveEmail(Request $request): JsonResponse
{
    try {
        Log::info("=== RECEPCIÓN DE CORREO ENTRANTE ===");
        Log::info("Datos del correo recibido:", $request->all());

        // Obtener datos del correo desde el JSON
        $emailData = $request->all();

        // Extraer información principal
        $recipients = data_get($emailData, 'recipients', []);
        $domain = data_get($emailData, 'domain', '');
        $envelopeSender = data_get($emailData, 'envelope_sender', '');
        $subject = data_get($emailData, 'headers.Subject.0', '');
        $messageId = data_get($emailData, 'message_id', '');

        // Contenido del correo (priorizar plaintext en lugar de HTML)
        // Usar preferentemente stripped_plaintext o plaintext y truncar a 255 caracteres si es necesario
        $content = data_get($emailData, 'body.stripped_plaintext',
                  data_get($emailData, 'body.plaintext', ''));

        // Truncar el contenido si excede 255 caracteres (límite de la columna)
        if (strlen($content) > 255) {
            $content = substr($content, 0, 252) . '...';
            Log::warning("Contenido truncado a 255 caracteres por limitación de la BD");
        }

        // Adjuntos - asegurarse que sea un array
        $attachments = data_get($emailData, 'attachments', []);
        if (!is_array($attachments)) {
            $attachments = []; // Asegurar que siempre sea un array
            Log::warning("Se recibieron adjuntos en formato no válido, se ha convertido a array vacío");
        }

        // CC y BCC (si vienen en headers)
        $ccEmails = $this->extractEmailsFromHeader(data_get($emailData, 'headers.Cc', []));
        $bccEmails = $this->extractEmailsFromHeader(data_get($emailData, 'headers.Bcc', []));

        Log::info("Correo procesado:", [
            'recipients' => $recipients,
            'domain' => $domain,
            'sender' => $envelopeSender,
            'subject' => $subject,
            'attachments_count' => is_array($attachments) ? count($attachments) : 0
        ]);

        // 1. Procesar recipients (correos corporativos que reciben)
        $recipientsResult = $this->processCompanyEmailRecipients($recipients);
        $ccResult = $this->processCompanyEmailRecipients($ccEmails);
        $bccResult = $this->processCompanyEmailRecipients($bccEmails);

        // 2. Procesar dominio
        $domainResult = $this->processDomain($domain);

        // 3. Procesar remitente (envelope_sender)
        $senderResult = $this->processRecipientSender($envelopeSender);

        // 4. Crear registro en tabla emails
        $emailSave = new Email();
        $emailSave->from = $senderResult['id']; // ID del remitente (Recipient)
        $emailSave->domain_id = $domainResult['id']; // ID del dominio
        $emailSave->to = json_encode($recipientsResult['ids']); // IDs de CompanyEmails
        $emailSave->cc = !empty($ccResult['ids']) ? json_encode($ccResult['ids']) : null;
        $emailSave->bcc = !empty($bccResult['ids']) ? json_encode($bccResult['ids']) : null;
        $emailSave->subject = $subject;
        $emailSave->content = $content; // Ahora contiene texto plano truncado
        $emailSave->scheduled_time = null; // Siempre null para correos recibidos
        $emailSave->created_at = now();
        $emailSave->save();

        Log::info("Email guardado en BD con ID: " . $emailSave->id);

        // 5. Procesar y guardar adjuntos
        if (!empty($attachments) && is_array($attachments)) {
            $this->processIncomingAttachments($attachments, $emailSave->id);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Correo recibido y procesado exitosamente',
            'email_id' => $emailSave->id,
            'attachments_processed' => is_array($attachments) ? count($attachments) : 0
        ], 200);

    } catch (Exception $e) {
        Log::error('Error procesando correo entrante: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'ok' => false,
            'message' => 'Error procesando correo entrante',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Procesar recipients como CompanyEmails
 */
private function processCompanyEmailRecipients(array $recipients): array
{
    if (empty($recipients)) {
        return ['ids' => [], 'emails' => []];
    }

    $processedRecipients = [];
    foreach ($recipients as $recipient) {
        // El recipient puede venir como string directo o como array
        $email = is_string($recipient) ? $recipient : data_get($recipient, 'address', '');

        if (!empty($email)) {
            $processedRecipients[] = $email;
        }
    }

    // Eliminar duplicados
    $processedRecipients = array_unique($processedRecipients);

    $allRecords = collect();
    foreach ($processedRecipients as $email) {
        $record = CompanyEmail::firstOrCreate(
            ['email' => $email],
            ['email' => $email]
        );
        $allRecords->push($record);

        Log::info("CompanyEmail procesado: {$email} (ID: {$record->id})");
    }

    return [
        'ids' => $allRecords->pluck('id')->values()->toArray(),
        'emails' => $allRecords->pluck('email')->values()->toArray(),
    ];
}

/**
 * Procesar dominio
 */
private function processDomain(string $domainName): array
{
    if (empty($domainName)) {
        Log::warning('Dominio vacío recibido');
        $domainName = 'unknown-domain.com'; // Fallback
    }

    // Usar Domain (modelo de dominio) en lugar de CompanyEmailDomain
    // Y usar 'domain' como nombre de columna según el modelo Domain
    $domain = \App\Models\Domain::firstOrCreate(
        ['domain' => $domainName],
        ['domain' => $domainName]
    );

    Log::info("Dominio procesado: {$domainName} (ID: {$domain->id})");

    return [
        'id' => $domain->id,
        'domain' => $domain->domain,
    ];
}

/**
 * Procesar remitente como Recipient
 */
private function processRecipientSender(string $senderEmail): array
{
    if (empty($senderEmail)) {
        Log::warning('Email del remitente vacío');
        $senderEmail = 'unknown@unknown.com'; // Fallback
    }

    $recipient = Recipient::firstOrCreate(
        ['email' => $senderEmail],
        ['email' => $senderEmail]
    );

    Log::info("Remitente procesado: {$senderEmail} (ID: {$recipient->id})");

    return [
        'id' => $recipient->id,
        'email' => $recipient->email,
    ];
}

/**
 * Extraer emails de headers (para CC y BCC)
 */
private function extractEmailsFromHeader(array $headerArray): array
{
    // Asegurarnos que headerArray sea realmente un array
    if (!is_array($headerArray)) {
        return [];
    }

    $emails = [];

    foreach ($headerArray as $headerValue) {
        // Extraer emails del header usando regex
        preg_match_all('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $headerValue, $matches);
        if (!empty($matches[1])) {
            $emails = array_merge($emails, $matches[1]);
        }
    }

    return array_unique($emails);
}

/**
 * Procesar adjuntos entrantes
 */
private function processIncomingAttachments(array $attachments, int $emailId): void
{
    foreach ($attachments as $attachment) {
        try {
            $filename = data_get($attachment, 'filename', 'unknown_file');
            $contentType = data_get($attachment, 'content_type', 'application/octet-stream');
            $size = data_get($attachment, 'size', 0);
            $url = data_get($attachment, 'url', '');

            if (empty($url)) {
                Log::warning("Adjunto sin URL: " . $filename);
                continue;
            }

            // Descargar archivo desde la URL
            $fileContent = $this->downloadAttachmentFromUrl($url);

            if ($fileContent === false) {
                Log::error("No se pudo descargar el adjunto: " . $filename);
                continue;
            }

            // Generar UUID para el nombre del archivo
            $uuid = Str::uuid();
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $newFileName = $uuid . '.' . $extension;

            // Guardar archivo en la carpeta 'receivedEmail'
            $storagePath = 'receivedEmail/' . $newFileName;
            Storage::put($storagePath, $fileContent);

            Log::info("Archivo descargado y guardado: {$filename} -> {$newFileName}");

            // Guardar en tabla Document
            $document = new Document();
            $document->size = round($size / 1024 / 1024, 2); // Tamaño en MB
            $document->type = '.' . $extension;
            $document->path = '/' . $storagePath;
            $document->save();

            Log::info("Documento guardado en BD: {$filename} (ID: {$document->id})");

            // Crear relación DocumentEmail
            $documentEmail = new DocumentEmail();
            $documentEmail->emails_id = $emailId;
            $documentEmail->documents_id = $document->id;
            $documentEmail->save();

            Log::info("Relación documento-email creada");

        } catch (Exception $e) {
            Log::error("Error procesando adjunto {$filename}: " . $e->getMessage());
        }
    }
}

/**
 * Descargar archivo desde URL
 */
private function downloadAttachmentFromUrl(string $url): string|false
{
    try {
        $response = Http::timeout(30)->get($url);

        if ($response->successful()) {
            return $response->body();
        }

        Log::error("Error descargando archivo desde URL: " . $url . " - Status: " . $response->status());
        return false;

    } catch (Exception $e) {
        Log::error("Excepción descargando archivo desde URL: " . $url . " - " . $e->getMessage());
        return false;
    }
}
}

// Ruta simple para tu controlador
// Route::post('/webhook/email-events', [MailerooController::class, 'handleWebhook']);

// secret key: fb9c68f3acd8987a3efac009adab79e3491906f030038bb51ca6f73b98a34a9c
// https://webhookapi.ngrok.app/api/fc849aae-4304-40d5-9744-28b08023961b/webhook/email-events

