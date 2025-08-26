<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class TokenController extends Controller
{
    private $statsFile = 'TokenController.json';
    
    public function __construct()
    {
        $this->initializeStatsFile();
    }

    private function initializeStatsFile()
    {
        $filePath = storage_path('app/' . $this->statsFile);
        
        if (!file_exists($filePath)) {
            $initialData = [
                'created_at' => now()->toISOString(),
                'last_updated' => now()->toISOString(),
                'tools' => [
                    'iawrite' => [
                        'usage_count' => 0,
                        'total_tokens' => 0,
                        'total_characters' => 0,
                        'total_words' => 0,
                        'average_response_time' => 0,
                        'successful_requests' => 0,
                        'failed_requests' => 0,
                        'last_used' => null
                    ],
                    'translate' => [
                        'usage_count' => 0,
                        'total_tokens' => 0,
                        'total_characters' => 0,
                        'total_words' => 0,
                        'average_response_time' => 0,
                        'successful_requests' => 0,
                        'failed_requests' => 0,
                        'last_used' => null
                    ],
                    'correct' => [
                        'usage_count' => 0,
                        'total_tokens' => 0,
                        'total_characters' => 0,
                        'total_words' => 0,
                        'average_response_time' => 0,
                        'successful_requests' => 0,
                        'failed_requests' => 0,
                        'last_used' => null
                    ],
                    'improve' => [
                        'usage_count' => 0,
                        'total_tokens' => 0,
                        'total_characters' => 0,
                        'total_words' => 0,
                        'average_response_time' => 0,
                        'successful_requests' => 0,
                        'failed_requests' => 0,
                        'last_used' => null
                    ]
                ],
                'totals' => [
                    'total_usage_count' => 0,
                    'total_tokens' => 0,
                    'total_characters' => 0,
                    'total_words' => 0,
                    'total_successful_requests' => 0,
                    'total_failed_requests' => 0,
                    'average_response_time' => 0,
                    'most_used_tool' => null
                ]
            ];
            
            file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }

    public function recordUsage(Request $request): JsonResponse
    {
        try {
            $tool = $request->input('tool');
            $tokens = (int) $request->input('tokens', 0);
            $characters = (int) $request->input('characters', 0);
            $words = (int) $request->input('words', 0);
            $responseTime = (float) $request->input('response_time', 0);
            $success = $request->input('success', true);

            if (!in_array($tool, ['iawrite', 'translate', 'correct', 'improve'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Herramienta no válida'
                ], 400);
            }

            $this->updateStats($tool, $tokens, $characters, $words, $responseTime, $success);

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas actualizadas correctamente'
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::recordUsage - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar las estadísticas'
            ], 500);
        }
    }

    private function updateStats($tool, $tokens, $characters, $words, $responseTime, $success)
    {
        $filePath = storage_path('app/' . $this->statsFile);
        $data = json_decode(file_get_contents($filePath), true);

        // Actualizar estadísticas de la herramienta específica
        $data['tools'][$tool]['usage_count']++;
        $data['tools'][$tool]['total_tokens'] += $tokens;
        $data['tools'][$tool]['total_characters'] += $characters;
        $data['tools'][$tool]['total_words'] += $words;
        $data['tools'][$tool]['last_used'] = now()->toISOString();

        if ($success) {
            $data['tools'][$tool]['successful_requests']++;
        } else {
            $data['tools'][$tool]['failed_requests']++;
        }

        // Calcular promedio de tiempo de respuesta
        $totalRequests = $data['tools'][$tool]['successful_requests'] + $data['tools'][$tool]['failed_requests'];
        if ($totalRequests > 1) {
            $currentAvg = $data['tools'][$tool]['average_response_time'];
            $data['tools'][$tool]['average_response_time'] = (($currentAvg * ($totalRequests - 1)) + $responseTime) / $totalRequests;
        } else {
            $data['tools'][$tool]['average_response_time'] = $responseTime;
        }

        // Actualizar totales generales
        $data['totals']['total_usage_count']++;
        $data['totals']['total_tokens'] += $tokens;
        $data['totals']['total_characters'] += $characters;
        $data['totals']['total_words'] += $words;

        if ($success) {
            $data['totals']['total_successful_requests']++;
        } else {
            $data['totals']['total_failed_requests']++;
        }

        // Calcular promedio general de tiempo de respuesta
        $totalGeneralRequests = $data['totals']['total_successful_requests'] + $data['totals']['total_failed_requests'];
        if ($totalGeneralRequests > 1) {
            $currentGeneralAvg = $data['totals']['average_response_time'];
            $data['totals']['average_response_time'] = (($currentGeneralAvg * ($totalGeneralRequests - 1)) + $responseTime) / $totalGeneralRequests;
        } else {
            $data['totals']['average_response_time'] = $responseTime;
        }

        // Determinar herramienta más usada
        $mostUsed = null;
        $maxUsage = 0;
        foreach ($data['tools'] as $toolName => $toolStats) {
            if ($toolStats['usage_count'] > $maxUsage) {
                $maxUsage = $toolStats['usage_count'];
                $mostUsed = $toolName;
            }
        }
        $data['totals']['most_used_tool'] = $mostUsed;

        $data['last_updated'] = now()->toISOString();

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getStats(): JsonResponse
    {
        try {
            $filePath = storage_path('app/' . $this->statsFile);
            $data = json_decode(file_get_contents($filePath), true);

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::getStats - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas'
            ], 500);
        }
    }

    public function getToolStats($tool): JsonResponse
    {
        try {
            if (!in_array($tool, ['iawrite', 'translate', 'correct', 'improve'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Herramienta no válida'
                ], 400);
            }

            $filePath = storage_path('app/' . $this->statsFile);
            $data = json_decode(file_get_contents($filePath), true);

            return response()->json([
                'success' => true,
                'tool' => $tool,
                'data' => $data['tools'][$tool]
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::getToolStats - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas de la herramienta'
            ], 500);
        }
    }

    public function getTotals(): JsonResponse
    {
        try {
            $filePath = storage_path('app/' . $this->statsFile);
            $data = json_decode(file_get_contents($filePath), true);

            return response()->json([
                'success' => true,
                'data' => $data['totals']
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::getTotals - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los totales'
            ], 500);
        }
    }

    public function resetStats(): JsonResponse
    {
        try {
            $filePath = storage_path('app/' . $this->statsFile);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->initializeStatsFile();

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas reiniciadas correctamente'
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::resetStats - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar las estadísticas'
            ], 500);
        }
    }

    public function exportStats(): JsonResponse
    {
        try {
            $filePath = storage_path('app/' . $this->statsFile);
            $data = json_decode(file_get_contents($filePath), true);

            // Agregar información adicional para el reporte
            $report = [
                'export_date' => now()->toISOString(),
                'export_summary' => [
                    'period_start' => $data['created_at'],
                    'period_end' => $data['last_updated'],
                    'total_days_active' => now()->diffInDays($data['created_at']),
                    'daily_average_usage' => round($data['totals']['total_usage_count'] / max(1, now()->diffInDays($data['created_at'])), 2)
                ],
                'statistics' => $data
            ];

            return response()->json([
                'success' => true,
                'report' => $report
            ], 200);

        } catch (Exception $e) {
            Log::error('TokenController::exportStats - Error:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar las estadísticas'
            ], 500);
        }
    }

    public function estimateTokens($text)
    {
        // Estimación simple de tokens basada en caracteres y palabras
        $chars = strlen($text);
        $words = str_word_count($text);
        
        // Estimación: ~4 caracteres por token en promedio
        $tokenEstimate = round($chars / 4);
        
        return [
            'estimated_tokens' => $tokenEstimate,
            'characters' => $chars,
            'words' => $words
        ];
    }
}