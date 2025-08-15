<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller; // Agregar esta línea
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class WhatsappController extends Controller
{


    public function sendMessage(Request $request)
    {
        Log::info("mensaje sendMessage", $request->all());

        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
            'user_id' => 'nullable|integer',
        ]);

        try {
            // Enviar mensaje al servicio de Node.js
            $response = Http::post('http://localhost:3000/send', [
                'number' => $request->number,
                'message' => $request->message
            ]);

            // Si el envío fue exitoso, guardar el mensaje en la base de datos
            if ($response->ok()) {
                // Buscar si existe una conversación con este número
                $conversation = \App\Models\WhatsApp\Conversation::where('number', $request->number)->first();

                // Si no existe, crear una nueva conversación
                if (!$conversation) {
                    $statusId = 1; // ID para conversación activa

                    $conversation = \App\Models\WhatsApp\Conversation::create([
                        'number' => $request->number,
                        'status_id' => $statusId,
                        // finish_at se deja vacío
                    ]);

                    Log::info('🆕 Nueva conversación creada para mensaje saliente', [
                        'conversation_id' => $conversation->id,
                        'phone' => $request->number
                    ]);
                }

                $generatedUuid = \Illuminate\Support\Str::uuid()->toString();

                // Guardar el mensaje saliente en la tabla de mensajes
                \App\Models\WhatsApp\Message::create([
                    'user_id' => $request->user_id,
                    'conversation_id' => $conversation->id,
                    'from' => '573025469239', // Tu número (mensaje saliente)
                    'to' => $request->number, // Número de destino
                    'wamid' => $generatedUuid,
                    'body' => $request->message,
                    'read' => true, // Los mensajes salientes se marcan como leídos
                    'type_msg' => 'text',
                ]);

                Log::info('✅ Mensaje saliente guardado correctamente', [
                    'conversation_id' => $conversation->id,
                    'from' => '573025469239',
                    'to' => $request->number,
                    'user_id' => $request->user_id
                ]);
            }

            return response()->json([
                'success' => $response->ok(),
                'node_response' => $response->json()
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al enviar mensaje de WhatsApp:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'No se pudo contactar con el servicio de WhatsApp',
                'details' => $e->getMessage()
            ], 500);
        }
    }









    public function receiveMessage(Request $request)
    {
        $payload = $request->validate([
            'from'    => 'required|string',
            'body'    => 'required|string',
            'id'      => 'nullable|string',
            'timestamp' => 'nullable|integer',
        ]);

        Log::info('📥 Mensaje WhatsApp entrante', $payload);

        try {
            // Extraer el número de teléfono del formato "XXXXXXXXXX@s.whatsapp.net"
            $phoneNumber = preg_replace('/@.*$/', '', $payload['from']);

            // Buscar si existe una conversación con este número
            $conversation = \App\Models\WhatsApp\Conversation::where('number', $phoneNumber)->first();

            // Si no existe, crear una nueva conversación
            if (!$conversation) {
                // Buscamos el status para conversaciones nuevas (asumimos que hay un status con ID 1 para conversaciones activas)
                // Puedes ajustar este ID según la configuración de tu sistema
                $statusId = 1; // ID para conversación activa

                $conversation = \App\Models\WhatsApp\Conversation::create([
                    'number' => $phoneNumber,
                    'status_id' => $statusId,
                    // finish_at se deja vacío como se solicitó
                ]);

                Log::info('🆕 Nueva conversación creada', [
                    'conversation_id' => $conversation->id,
                    'phone' => $phoneNumber
                ]);
            }

            // Guardar el mensaje en la tabla de mensajes
            \App\Models\WhatsApp\Message::create([
                'conversation_id' => $conversation->id,
                'from' => $phoneNumber,
                'to' => 'system',
                'wamid' => $payload['id'] ?? null,
                'body' => $payload['body'],
                'read' => false,
                'type_msg' => 'text', // Valor por defecto, se puede ajustar si hay más tipos
            ]);

            Log::info('✅ Mensaje guardado correctamente', [
                'conversation_id' => $conversation->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recibido con éxito',
                'conversation_id' => $conversation->id
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al procesar mensaje de WhatsApp:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje',
                'details' => $e->getMessage()
            ], 500);
        }
    }





    public function getMessage(Request $request)
    {

        try {
            // Obtenemos todas las conversaciones con sus mensajes y la relación status
            $query = \App\Models\WhatsApp\Conversation::with([
                'messages' => function($query) {
                    $query->orderBy('created_at', 'asc'); // Ordenamos los mensajes del más antiguo al más reciente
                },
                'status'
            ]);

            // Filtrar por número de teléfono si se proporciona
            if ($request->has('phone')) {
                $phone = $request->input('phone');
                Log::info("🔍 Filtrando por número de teléfono", ['phone' => $phone]);
                $query->where('number', 'like', "%{$phone}%");
            }

            // Filtrar por estado si se proporciona
            if ($request->has('status_id')) {
                $statusId = $request->input('status_id');
                Log::info("🔍 Filtrando por estado", ['status_id' => $statusId]);
                $query->where('status_id', $statusId);
            }

            // Si se proporciona un ID de conversación específico
            if ($request->has('conversation_id')) {
                $conversationId = $request->input('conversation_id');
                Log::info("🔍 Obteniendo conversación específica", ['conversation_id' => $conversationId]);
                $query->where('id', $conversationId);
            }

            // Ordenar por fecha de creación (más recientes primero)
            $query->orderBy('created_at', 'desc');

            // Paginar resultados (por defecto 10 conversaciones por página, configurable con el parámetro 'limit')
            $limit = $request->input('limit', 10);
            $conversations = $query->paginate($limit);



            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al obtener conversaciones de WhatsApp:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener las conversaciones',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function getStatus(Request $request)
    {
        Log::info("⭐ Iniciando getStatus", [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all()
        ]);

        try {
            Log::info("🔄 Intentando conectar con Node.js");
            $response = Http::get('http://localhost:3000/qr-status');
            Log::info("📥 Respuesta de Node.js", [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                throw new \Exception('Error al conectar con el servidor de WhatsApp');
            }

            $data = $response->json();

            $result = [
                'success' => $data['success'] ?? false,
                'authenticated' => $data['authenticated'] ?? false,
                'connected' => $data['connected'] ?? false,
                'qr' => $data['qr'] ?? null,
                'user' => $data['user'] ?? null
            ];

            Log::info("✅ Enviando respuesta al frontend", $result);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('❌ Error WhatsApp Status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'No se pudo contactar con el servicio de WhatsApp',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}
