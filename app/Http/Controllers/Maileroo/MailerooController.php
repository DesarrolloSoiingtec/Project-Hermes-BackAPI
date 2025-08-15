<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Maileroo\Message;
use App\Models\Maileroo\Recipient;
use App\Models\Maileroo\Event;
use App\Models\Maileroo\EventMetadata;
use Carbon\Carbon;

class MailerooController extends Controller
{
    public function newEvent(Request $request)
    // {
    //     log::info("request:", $request->all());

    //     return response()->json(['success' => true]);
    //}
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
}

// Ruta simple para tu controlador
// Route::post('/webhook/email-events', [MailerooController::class, 'handleWebhook']);

// secret key: fb9c68f3acd8987a3efac009adab79e3491906f030038bb51ca6f73b98a34a9c
// https://webhookapi.ngrok.app/api/fc849aae-4304-40d5-9744-28b08023961b/webhook/email-events

