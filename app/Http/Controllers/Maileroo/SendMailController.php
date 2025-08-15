<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Exception;

// test, primero commit
/**
 * Controlador para envío de emails usando Maileroo API como prioridad
 * y SMTP como fallback
 */
class SendMailController extends Controller
{
    /**
     * Método principal para envío de correos electrónicos
     * Maneja tanto FormData con payload JSON como requests JSON directos
     * Prioriza Maileroo API, fallback a SMTP
     *
     * @param Request $request - Request HTTP con datos del email
     * @return JsonResponse - Respuesta JSON con estado del envío
     */
    public function sendMail(Request $request): JsonResponse
    {
        // ============= FASE 1: LOGGING INICIAL =============
        // Registrar todo el contenido del request para debugging completo
        log::info("contenido del request:",$request->all());
        // Verificar si el request viene como JSON o form-data y el content-type
        Log::info('llegue a sendMail', ['is_json' => $request->isJson(), 'content_type' => $request->header('Content-Type')]);
        // Capturar el cuerpo raw del request (útil para debugging problemas de parsing)
        Log::debug('raw_body', ['body' => $request->getContent()]);

        // ============= FASE 2: EXTRACCIÓN Y PARSING DEL PAYLOAD =============
        // Determinar el tipo de request y extraer el payload de datos
        // Laravel puede recibir datos de dos formas principales:
        // 1. Como FormData con un campo 'payload' que contiene JSON
        // 2. Como request JSON directo

        // CASO A: FormData con campo 'payload' que contiene JSON string
        if ($request->has('payload') && is_string($request->input('payload'))) {
            // Cuando el frontend envía FormData + archivos, los datos JSON
            // van en un campo llamado 'payload' como string
            try {
                // Decodificar el JSON string a array asociativo PHP
                $payload = json_decode($request->input('payload'), true);
                // Verificar si hubo errores en el parsing del JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Error parseando payload JSON', ['error' => json_last_error_msg()]);
                    return response()->json([
                        'ok' => false,
                        'message' => 'Invalid JSON in payload field',
                        'error' => json_last_error_msg()
                    ], 400);
                }
                Log::debug('payload_from_formdata', ['payload' => $payload]);
            } catch (Exception $e) {
                // Capturar cualquier excepción durante el parsing
                Log::error('Exception parseando payload', ['error' => $e->getMessage()]);
                return response()->json([
                    'ok' => false,
                    'message' => 'Error parsing payload',
                    'error' => $e->getMessage()
                ], 400);
            }
        } else {
            // CASO B: Request JSON directo - usar todos los datos del request
            // Cuando el frontend envía application/json directamente
            $payload = $request->all();
            Log::debug('payload_parsed', ['payload' => $payload]);
        }

        $apiUrl = env('MAILER_API_URL');
        if ($apiUrl) {
            try {
                $attachmentsList = data_get($payload, 'attachments', []);
                $processedAttachments = [];

                Log::debug('Procesando attachments API', [
                    'attachments' => $attachmentsList,
                    'files_in_request' => array_keys($request->allFiles()),
                    'is_json_request' => $request->isJson(),
                    'has_files_field' => $request->hasFile('files')
                ]);

                foreach ($attachmentsList as $attachment) {
                    $attachmentName = data_get($attachment, 'name');
                    $fromLibrary = data_get($attachment, 'fromLibrary', false);
                    $libraryId = data_get($attachment, 'libraryId');
                    $fieldName = data_get($attachment, 'fieldName'); // Nuevo campo

                    // Caso 1: Archivo subido como multipart/form-data con fieldName específico
                    if (!$fromLibrary && $fieldName && $request->hasFile($fieldName)) {
                        $file = $request->file($fieldName);

                        if ($file->isValid()) {
                            $fileContent = file_get_contents($file->getRealPath());
                            if ($fileContent !== false) {
                                $processedAttachments[] = [
                                    'filename' => $file->getClientOriginalName(),
                                    'content' => base64_encode($fileContent),
                                    'content_type' => $file->getMimeType()
                                ];

                                Log::debug('Attachment procesado (multipart con fieldName)', [
                                    'name' => $attachmentName,
                                    'field_name' => $fieldName,
                                    'original_name' => $file->getClientOriginalName(),
                                    'mime_type' => $file->getMimeType(),
                                    'size' => strlen($fileContent)
                                ]);
                            }
                        }
                    }
                    // Caso 1b: Fallback para compatibilidad - buscar en campo 'files' genérico
                    elseif (!$fromLibrary && !$fieldName && $request->hasFile('files')) {
                        $files = $request->file('files');
                        // Si hay múltiples archivos, 'files' es un array
                        if (!is_array($files)) {
                            $files = [$files];
                        }

                        $fileProcessed = false;
                        foreach ($files as $file) {
                            if ($file->isValid() && $file->getClientOriginalName() === $attachmentName) {
                                $fileContent = file_get_contents($file->getRealPath());
                                if ($fileContent !== false) {
                                    $processedAttachments[] = [
                                        'filename' => $file->getClientOriginalName(),
                                        'content' => base64_encode($fileContent),
                                        'content_type' => $file->getMimeType()
                                    ];

                                    Log::debug('Attachment procesado (multipart files fallback)', [
                                        'name' => $attachmentName,
                                        'original_name' => $file->getClientOriginalName(),
                                        'mime_type' => $file->getMimeType(),
                                        'size' => strlen($fileContent)
                                    ]);

                                    $fileProcessed = true;
                                    break;
                                }
                            }
                        }

                        if (!$fileProcessed) {
                            Log::warning('Archivo no encontrado en files field', [
                                'looking_for' => $attachmentName,
                                'available_files' => array_map(fn($f) => $f->getClientOriginalName(), $files)
                            ]);
                        }
                    }
                    // Caso 2: Archivo enviado como base64 en JSON
                    elseif (isset($attachment['content']) && isset($attachment['filename'])) {
                        $processedAttachments[] = [
                            'filename' => $attachment['filename'],
                            'content' => $attachment['content'], // Ya debe estar en base64
                            'content_type' => data_get($attachment, 'type', 'application/octet-stream')
                        ];

                        Log::debug('Attachment procesado (base64)', [
                            'filename' => $attachment['filename'],
                            'content_type' => data_get($attachment, 'type'),
                            'content_length' => strlen($attachment['content'])
                        ]);
                    }
                    // Caso 3: Archivo desde librería (buscar por libraryId o name)
                    elseif ($fromLibrary || ($attachmentName && !isset($attachment['content']))) {
                        // Buscar el archivo en storage/app/public/
                        $storagePaths = [
                            'public/fileCourse/' . $attachmentName,
                            'public/users/' . $attachmentName,
                            'public/persons/' . $attachmentName,
                            'public/signature/' . $attachmentName,
                            'public/helpVideo/' . $attachmentName,
                            'public/helpVideos/' . $attachmentName,
                            'public/' . $attachmentName
                        ];

                        $fileFound = false;
                        foreach ($storagePaths as $storagePath) {
                            if (Storage::exists($storagePath)) {
                                try {
                                    $fileContent = Storage::get($storagePath);
                                    $processedAttachments[] = [
                                        'filename' => $attachmentName,
                                        'content' => base64_encode($fileContent),
                                        'content_type' => data_get($attachment, 'type', 'application/octet-stream')
                                    ];

                                    Log::debug('Attachment procesado (desde storage/library)', [
                                        'name' => $attachmentName,
                                        'storage_path' => $storagePath,
                                        'content_type' => data_get($attachment, 'type'),
                                        'size' => strlen($fileContent),
                                        'from_library' => $fromLibrary,
                                        'library_id' => $libraryId
                                    ]);

                                    $fileFound = true;
                                    break;
                                } catch (Exception $e) {
                                    Log::error('Error leyendo archivo desde storage', [
                                        'name' => $attachmentName,
                                        'storage_path' => $storagePath,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }

                        if (!$fileFound) {
                            Log::warning('Archivo no encontrado en storage', [
                                'name' => $attachmentName,
                                'from_library' => $fromLibrary,
                                'library_id' => $libraryId,
                                'searched_paths' => $storagePaths
                            ]);
                        }
                    }
                    else {
                        Log::warning('Attachment sin archivo válido', [
                            'attachment' => $attachment,
                            'has_files_field' => $request->hasFile('files'),
                            'has_content' => isset($attachment['content']),
                            'from_library' => $fromLibrary
                        ]);
                    }
                }

                if (!empty($processedAttachments)) {
                    $payload['attachments'] = $processedAttachments;
                    Log::info('Attachments agregados al payload', ['count' => count($processedAttachments)]);
                } else {
                    unset($payload['attachments']);
                    Log::info('No hay attachments válidos para procesar');
                }

                Log::info('Enviando a Maileroo API', [
                    'url' => $apiUrl,
                    'payload_keys' => array_keys($payload),
                    'attachments_count' => count($processedAttachments)
                ]);

                $response = Http::withBasicAuth(env('MAIL_USERNAME'), env('MAIL_PASSWORD'))
                    ->acceptJson()
                    ->timeout(60)
                    ->post($apiUrl, $payload);

                Log::info('Respuesta Maileroo API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'ok' => true,
                        'message' => 'Enviado a Maileroo API',
                        'response' => $response->json(),
                        'attachments_sent' => count($processedAttachments)
                    ], 200);
                }

                Log::error('Maileroo API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Maileroo API returned an error',
                    'status' => $response->status(),
                    'body' => $response->body()
                ], max(400, $response->status()));

            } catch (Exception $e) {
                Log::error('Error enviando a Maileroo API', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'ok' => false,
                    'message' => 'Error sending to Maileroo API',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // Fallback SMTP con soporte para base64
        try {
            Log::info('MAILER_API_URL no definido, usando fallback SMTP');

            $fromAddress = data_get($payload, 'from.address', config('mail.from.address'));
            $fromName = data_get($payload, 'from.display_name', config('mail.from.name'));
            $subject = data_get($payload, 'subject', '(sin asunto)');
            $content = data_get($payload, 'content', data_get($payload, 'plain', ''));

            $toList = data_get($payload, 'to', []);
            $ccList = data_get($payload, 'cc', []);
            $bccList = data_get($payload, 'bcc', []);

            if (empty($toList) && empty($ccList) && empty($bccList)) {
                Log::warning('Intento de envío sin destinatarios', ['payload' => $payload]);
                return response()->json([
                    'ok' => false,
                    'message' => 'No recipients provided in payload (to, cc, or bcc).'
                ], 400);
            }

            $attachmentsList = data_get($payload, 'attachments', []);
            Log::debug('Procesando attachments SMTP', [
                'attachments' => $attachmentsList,
                'files_in_request' => array_keys($request->allFiles()),
                'is_json_request' => $request->isJson(),
                'has_files_field' => $request->hasFile('files')
            ]);

            // Para SMTP, necesitamos crear archivos temporales si vienen en base64
            $tempFiles = [];

            Mail::send('emails.plantillaDefault', [
                'content' => $content,
                'subject' => $subject,
                'fromAddress' => $fromAddress,
                'fromName' => $fromName,
                'payload' => $payload,
                'appName' => config('app.name'),
            ], function ($message) use ($fromAddress, $fromName, $subject, $toList, $ccList, $bccList, $attachmentsList, $request, &$tempFiles) {
                $message->from($fromAddress, $fromName);
                $message->subject($subject);

                // Procesar destinatarios (código igual que antes)
                foreach ($toList as $to) {
                    if (is_string($to)) {
                        $message->to($to);
                    } elseif (is_array($to) || is_object($to)) {
                        $address = data_get($to, 'address');
                        $name = data_get($to, 'display_name', null);
                        if ($address) {
                            $message->to($address, $name);
                        }
                    }
                }

                foreach ($ccList as $cc) {
                    if (is_string($cc)) {
                        $message->cc($cc);
                    } elseif (is_array($cc) || is_object($cc)) {
                        $address = data_get($cc, 'address');
                        $name = data_get($cc, 'display_name', null);
                        if ($address) {
                            $message->cc($address, $name);
                        }
                    }
                }

                foreach ($bccList as $bcc) {
                    if (is_string($bcc)) {
                        $message->bcc($bcc);
                    } elseif (is_array($bcc) || is_object($bcc)) {
                        $address = data_get($bcc, 'address');
                        $name = data_get($bcc, 'display_name', null);
                        if ($address) {
                            $message->bcc($address, $name);
                        }
                    }
                }

                // Procesar attachments - usando la misma lógica que arriba pero para SMTP
                foreach ($attachmentsList as $attachment) {
                    $attachmentName = data_get($attachment, 'name');
                    $attachmentType = data_get($attachment, 'type');
                    $fromLibrary = data_get($attachment, 'fromLibrary', false);
                    $fieldName = data_get($attachment, 'fieldName'); // Nuevo campo

                    // Caso 1: Archivo subido como multipart con fieldName específico
                    if (!$fromLibrary && $fieldName && $request->hasFile($fieldName)) {
                        $file = $request->file($fieldName);
                        if ($file->isValid()) {
                            $message->attach($file->getRealPath(), [
                                'as' => $file->getClientOriginalName(),
                                'mime' => $attachmentType ?: $file->getMimeType()
                            ]);

                            Log::debug('SMTP: Archivo adjuntado con fieldName', [
                                'name' => $attachmentName,
                                'field_name' => $fieldName,
                                'original_name' => $file->getClientOriginalName()
                            ]);
                        }
                    }
                    // Caso 1b: Fallback para compatibilidad - buscar en campo 'files' genérico
                    elseif (!$fromLibrary && !$fieldName && $request->hasFile('files')) {
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

                                Log::debug('SMTP: Archivo adjuntado desde files fallback', [
                                    'name' => $attachmentName,
                                    'original_name' => $file->getClientOriginalName()
                                ]);
                                break;
                            }
                        }
                    }
                    // Caso 2: Archivo en base64 - crear archivo temporal
                    elseif (isset($attachment['content']) && isset($attachment['filename'])) {
                        try {
                            $content = base64_decode($attachment['content']);
                            $tempPath = tempnam(sys_get_temp_dir(), 'email_attachment_');
                            file_put_contents($tempPath, $content);

                            $tempFiles[] = $tempPath; // Para limpieza posterior

                            $message->attach($tempPath, [
                                'as' => $attachment['filename'],
                                'mime' => $attachmentType ?: 'application/octet-stream'
                            ]);

                            Log::debug('Archivo temporal creado para SMTP', [
                                'filename' => $attachment['filename'],
                                'temp_path' => $tempPath
                            ]);
                        } catch (Exception $e) {
                            Log::error('Error creando archivo temporal', [
                                'filename' => $attachment['filename'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    // Caso 3: Archivo guardado en storage - crear archivo temporal
                    elseif ($fromLibrary || ($attachmentName && !isset($attachment['content']))) {
                        $storagePaths = [
                            'public/fileCourse/' . $attachmentName,
                            'public/users/' . $attachmentName,
                            'public/persons/' . $attachmentName,
                            'public/signature/' . $attachmentName,
                            'public/helpVideo/' . $attachmentName,
                            'public/helpVideos/' . $attachmentName,
                            'public/' . $attachmentName
                        ];

                        $fileFound = false;
                        foreach ($storagePaths as $storagePath) {
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

                                    Log::debug('Archivo desde storage adjuntado a SMTP', [
                                        'name' => $attachmentName,
                                        'storage_path' => $storagePath,
                                        'temp_path' => $tempPath
                                    ]);

                                    $fileFound = true;
                                    break;
                                } catch (Exception $e) {
                                    Log::error('Error procesando archivo desde storage para SMTP', [
                                        'name' => $attachmentName,
                                        'storage_path' => $storagePath,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }

                        if (!$fileFound) {
                            Log::warning('Archivo no encontrado en storage para SMTP', [
                                'name' => $attachmentName,
                                'searched_paths' => $storagePaths
                            ]);
                        }
                    }
                }
            });

            // Limpiar archivos temporales
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            Log::info('Envío SMTP realizado (fue al mailer configurado)');

            return response()->json([
                'ok' => true,
                'message' => 'Enviado por SMTP (fallback)'
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

            Log::error('Error en envío SMTP fallback', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Error sending by SMTP fallback',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
