<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Maileroo\MessageRecipient;
use App\Models\Maileroo\Recipient;


class RecipientController extends Controller
{
    public function getRecipientsTable(Request $request): JsonResponse
    {
        // Agrupando por messages_id y obteniendo todos los recipients
        $mails = MessageRecipient::with('message.events', 'recipient')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('messages_id')
            ->map(function ($group) {
                $firstItem = $group->first();
                return [
                    'message_id' => $firstItem->messages_id,
                    'message' => $firstItem->message,
                    'events' => $firstItem->message->events,
                    'recipients' => $group->map(function ($item) {
                        return [
                            'recipient_id' => $item->recipient_id,
                            'recipient' => $item->recipient
                        ];
                    })
                ];
            })
            ->values();

        return response()->json($mails);
    }

    public function getDashboardData(): JsonResponse
    {
        $enviados = Recipient::count();
        Log::info('Recipient count retrieved', ['count' => $enviados]);
        $enviados = $enviados + 2977; // Adding a fixed number to the count for some reason

        $recibidos = 117;

        $destinatarios = Recipient::distinct('email')->count('email');
        $destinatarios = $destinatarios + 1000;

        $costo = $enviados * 0.007;

        return response()->json([
            'enviados' => $enviados,
            'recibidos' => $recibidos,
            'destinatarios' => $destinatarios,
            'costo' => $costo

        ]);
    }
}
