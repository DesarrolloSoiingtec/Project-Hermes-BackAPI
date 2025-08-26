<?php

namespace App\Http\Controllers\Maileroo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class IAController extends Controller
{
    private $tokenController;
    
    public function __construct()
    {
        $this->tokenController = new TokenController();
    }
     public function IAwrite(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        try {
            // Obtener la API key desde el archivo .env
            $apiKey = env('IA_API_KEY');
            
            if (!$apiKey) {
                Log::error('API Key de Groq no encontrada en las variables de entorno');
                return response()->json([
                    'success' => false,
                    'message' => 'API Key no configurada'
                ], 500);
            }

            // Obtener parámetros del request (ahora desde query parameters)
            $type = $request->query('type', 'email');
            $tone = $request->query('tone', 'professional');
            $description = $request->query('description', '');
            $length = $request->query('length', 3);
            $lengthLabel = $request->query('lengthLabel', 'Medio');

            // Log de parámetros recibidos
            Log::info('IAwrite - Parámetros recibidos (GET):', [
                'type' => $type,
                'tone' => $tone,
                'description' => $description,
                'length' => $length,
                'lengthLabel' => $lengthLabel,
                'all_query_params' => $request->query()
            ]);

            // Validar que se proporcione una descripción
            if (empty($description)) {
                Log::warning('IAwrite - Descripción vacía proporcionada');
                return response()->json([
                    'success' => false,
                    'message' => 'La descripción del contenido es requerida'
                ], 400);
            }

            // Construir el prompt profesional
            $prompt = $this->buildProfessionalPrompt($type, $tone, $description, $lengthLabel);
            
            Log::info('IAwrite - Prompt construido:', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);

            // Realizar petición a Groq AI con GPT-OSS-120B
            Log::info('IAwrite - Iniciando petición a Groq AI con modelo: openai/gpt-oss-120b');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-120b',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2500,
                'temperature' => 0.7,
                'top_p' => 0.9
            ]);

            Log::info('IAwrite - Petición completada:', [
                'status_code' => $response->status(),
                'response_size' => strlen($response->body())
            ]);

            // Verificar si la petición fue exitosa
            if ($response->successful()) {
                Log::info('IAwrite - Respuesta exitosa de Groq AI');
                
                $responseData = $response->json();
                
                Log::info('IAwrite - Datos de respuesta:', [
                    'response_structure' => array_keys($responseData),
                    'choices_count' => count($responseData['choices'] ?? [])
                ]);
                
                // Extraer el contenido generado
                $generatedText = $responseData['choices'][0]['message']['content'] ?? 'No se pudo extraer el mensaje';
                
                // Limpiar el texto (remover espacios extra)
                $cleanText = trim($generatedText);
                
                // Log del texto generado
                Log::info('IAwrite - Texto generado exitosamente:', [
                    'type' => $type,
                    'tone' => $tone,
                    'length' => $lengthLabel,
                    'text_length' => strlen($cleanText),
                    'word_count' => str_word_count($cleanText),
                    'text_preview' => substr($cleanText, 0, 150) . '...'
                ]);

                // Preparar respuesta final
                $finalResponse = [
                    'success' => true,
                    'message' => $cleanText,
                    'generatedText' => $cleanText,
                    'result' => $cleanText,
                    'metadata' => [
                        'type' => $type,
                        'tone' => $tone,
                        'length' => $lengthLabel,
                        'word_count' => str_word_count($cleanText),
                        'char_count' => strlen($cleanText)
                    ]
                ];

                Log::info('IAwrite - Respuesta final preparada:', [
                    'response_keys' => array_keys($finalResponse),
                    'metadata' => $finalResponse['metadata']
                ]);

                // Registrar estadísticas de uso
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $estimatedTokens = $this->tokenController->estimateTokens($cleanText);
                
                $this->recordToolUsage('iawrite', $estimatedTokens, $responseTime, true);

                return response()->json($finalResponse, 200);

            } else {
                Log::error('IAwrite - Error en la petición a Groq AI:', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'request_data' => [
                        'model' => 'openai/gpt-oss-120b',
                        'max_tokens' => 2500,
                        'temperature' => 0.7
                    ]
                ]);

                // Registrar estadísticas de error
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $this->recordToolUsage('iawrite', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

                return response()->json([
                    'success' => false,
                    'message' => 'Error en la conexión con Groq AI',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (Exception $e) {
            Log::error('IAwrite - Excepción capturada:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Registrar estadísticas de error
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $this->recordToolUsage('iawrite', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildProfessionalPrompt($type, $tone, $description, $lengthLabel)
    {
        // Mapear tipos de texto a descripciones específicas con contexto y estructura
        $typeMap = [
            'email' => [
                'name' => 'correo electrónico profesional',
                'structure' => 'Estructura: Saludo apropiado → Propósito/contexto → Desarrollo del mensaje → Llamada a la acción (si aplica) → Cierre profesional',
                'context' => 'Debe ser directo, claro y orientado a resultados. Considera el nivel jerárquico del destinatario.',
                'examples' => 'Usa conectores profesionales como "En relación a", "Con el propósito de", "A fin de"'
            ],
            'letter' => [
                'name' => 'carta formal',
                'structure' => 'Estructura: Encabezado → Fecha → Destinatario → Saludo formal → Introducción → Cuerpo principal → Conclusión → Despedida formal',
                'context' => 'Debe seguir protocolos formales estrictos y mantener respeto institucional.',
                'examples' => 'Utiliza fórmulas como "Tengo el honor de dirigirme", "Me dirijo a usted con el fin de"'
            ],
            'blog' => [
                'name' => 'artículo de blog',
                'structure' => 'Estructura: Título atractivo → Introducción gancho → Desarrollo con subtemas → Conclusión con valor añadido',
                'context' => 'Debe ser valioso, engaging y optimizado para engagement digital.',
                'examples' => 'Incluye elementos como listas, preguntas retóricas y llamadas a la acción sutiles'
            ],
            'product' => [
                'name' => 'descripción de producto',
                'structure' => 'Estructura: Beneficio principal → Características clave → Diferenciadores → Valor agregado → Call-to-action',
                'context' => 'Enfócate en beneficios sobre características. Resuelve problemas específicos del cliente.',
                'examples' => 'Usa power words como "innovador", "eficiente", "confiable", "exclusivo"'
            ],
            'social' => [
                'name' => 'publicación para redes sociales',
                'structure' => 'Estructura: Hook inicial → Contenido valor → Engagement element → Hashtags estratégicos',
                'context' => 'Debe generar interacción, ser shareable y alineado con algoritmos de redes sociales.',
                'examples' => 'Incluye elementos como emojis estratégicos, preguntas directas, y CTAs claros'
            ],
            'proposal' => [
                'name' => 'propuesta comercial',
                'structure' => 'Estructura: Resumen ejecutivo → Problema/oportunidad → Solución propuesta → Beneficios → Próximos pasos',
                'context' => 'Debe ser persuasiva, basada en datos y enfocada en ROI del cliente.',
                'examples' => 'Incluye métricas, testimonios implícitos y argumentos de autoridad'
            ],
            'press' => [
                'name' => 'comunicado de prensa',
                'structure' => 'Estructura: Titular impactante → Lead (5W+1H) → Cuerpo con quotes → Información corporativa → Contacto',
                'context' => 'Debe ser newsworthy, objetivo y seguir estándares periodísticos.',
                'examples' => 'Usa tercera persona, datos verificables y quotes relevantes'
            ],
            'creative' => [
                'name' => 'contenido creativo',
                'structure' => 'Estructura: Concepto central → Desarrollo narrativo → Elementos sensoriales → Mensaje/moraleja',
                'context' => 'Debe ser original, memorable y emocionalmente conectivo.',
                'examples' => 'Utiliza storytelling, metáforas y lenguaje sensorial'
            ]
        ];

        // Mapear tonos con instrucciones específicas y técnicas de redacción
        $toneMap = [
            'professional' => [
                'description' => 'profesional y formal, manteniendo un lenguaje corporativo apropiado',
                'techniques' => 'Usa voz activa, verbos precisos, evita jerga casual. Mantén objetividad y respeto.',
                'vocabulary' => 'Términos como: "implementar", "optimizar", "desarrollar", "coordinar", "analizar"',
                'avoid' => 'Contracciones, slang, expresiones coloquiales, emociones excesivas'
            ],
            'friendly' => [
                'description' => 'amigable y cercano, pero manteniendo el respeto',
                'techniques' => 'Usa pronombres inclusivos, preguntas retóricas suaves, expresiones de cortesía.',
                'vocabulary' => 'Términos como: "nos complace", "estaremos encantados", "esperamos", "agradeceríamos"',
                'avoid' => 'Excesiva familiaridad, tuteo inapropiado, humor arriesgado'
            ],
            'formal' => [
                'description' => 'extremadamente formal y protocolar',
                'techniques' => 'Estructura rígida, fórmulas de cortesía tradicionales, tercera persona cuando sea apropiado.',
                'vocabulary' => 'Términos como: "distinguido", "honorable", "respetuosamente", "protocolarmente"',
                'avoid' => 'Informalidades, contracciones, expresiones modernas casuales'
            ],
            'casual' => [
                'description' => 'casual y relajado, como una conversación informal',
                'techniques' => 'Lenguaje conversacional, contracciones apropiadas, estructura flexible.',
                'vocabulary' => 'Términos como: "genial", "perfecto", "súper", "básicamente", "obviamente"',
                'avoid' => 'Excesiva formalidad, tecnicismos innecesarios, estructuras rígidas'
            ],
            'persuasive' => [
                'description' => 'persuasivo y convincente, enfocado en generar acción',
                'techniques' => 'Usa prueba social, escasez, autoridad, reciprocidad. Incluye beneficios específicos.',
                'vocabulary' => 'Términos como: "exclusivo", "limitado", "comprobado", "garantizado", "inmediatamente"',
                'avoid' => 'Sobrepromesas, presión excesiva, claims no verificables'
            ],
            'enthusiastic' => [
                'description' => 'entusiasta y energético, transmitiendo emoción positiva',
                'techniques' => 'Exclamaciones moderadas, adjetivos positivos, ritmo dinámico, verbos de acción.',
                'vocabulary' => 'Términos como: "fantástico", "increíble", "revolucionario", "emocionante", "extraordinario"',
                'avoid' => 'Exceso de exclamaciones, superlativos exagerados, tono artificial'
            ],
            'informative' => [
                'description' => 'informativo y educativo, enfocado en claridad',
                'techniques' => 'Estructura lógica, definiciones claras, ejemplos concretos, transiciones suaves.',
                'vocabulary' => 'Términos como: "específicamente", "por ejemplo", "en consecuencia", "adicionalmente"',
                'avoid' => 'Jerga técnica sin explicar, asunciones sobre conocimiento previo'
            ],
            'empathetic' => [
                'description' => 'empático y comprensivo, mostrando sensibilidad',
                'techniques' => 'Reconocimiento de emociones, validación de preocupaciones, lenguaje inclusivo.',
                'vocabulary' => 'Términos como: "comprendemos", "reconocemos", "valoramos", "apreciamos", "entendemos"',
                'avoid' => 'Minimizar problemas, respuestas robóticas, falta de humanización'
            ]
        ];

        // Mapear longitudes con especificaciones precisas
        $lengthMap = [
            'Muy corto' => [
                'words' => 'máximo 100 palabras',
                'guidelines' => 'Máxima concisión. Cada palabra debe aportar valor. Elimina adornos innecesarios.',
                'structure' => 'Mensaje directo → Acción/conclusión'
            ],
            'Corto' => [
                'words' => '100-200 palabras',
                'guidelines' => 'Breve pero completo. Incluye solo información esencial. Párrafos cortos.',
                'structure' => 'Introducción breve → Punto principal → Cierre'
            ],
            'Medio' => [
                'words' => '200-400 palabras',
                'guidelines' => 'Desarrollo equilibrado. Permite elaboración de ideas sin redundancia.',
                'structure' => 'Introducción → Desarrollo principal → Elaboración → Conclusión'
            ],
            'Largo' => [
                'words' => '400-600 palabras',
                'guidelines' => 'Contenido detallado. Incluye contexto, ejemplos y desarrollo completo.',
                'structure' => 'Introducción → Múltiples puntos principales → Elaboración detallada → Conclusión robusta'
            ],
            'Muy largo' => [
                'words' => '600+ palabras',
                'guidelines' => 'Tratamiento exhaustivo. Incluye múltiples perspectivas, ejemplos detallados y análisis profundo.',
                'structure' => 'Introducción completa → Múltiples secciones → Subsecciones → Análisis → Conclusión comprehensive'
            ]
        ];

        $typeInfo = $typeMap[$type] ?? ['name' => 'texto', 'structure' => '', 'context' => '', 'examples' => ''];
        $toneInfo = $toneMap[$tone] ?? ['description' => 'profesional', 'techniques' => '', 'vocabulary' => '', 'avoid' => ''];
        $lengthInfo = $lengthMap[$lengthLabel] ?? ['words' => 'extensión moderada', 'guidelines' => '', 'structure' => ''];

        return "# PROMPT ESPECIALIZADO PARA REDACCIÓN PROFESIONAL

        ## PERFIL DEL ASISTENTE
        Eres un redactor senior con 15+ años de experiencia en comunicación corporativa, marketing de contenidos y relaciones públicas. Tu especialidad es crear contenido que no solo comunica, sino que genera resultados medibles y engagement auténtico.

        ## ESPECIFICACIONES DEL PROYECTO

        ### TIPO DE CONTENIDO: {$typeInfo['name']}
        - **Contexto estratégico**: {$typeInfo['context']}
        - **Estructura requerida**: {$typeInfo['structure']}
        - **Elementos distintivos**: {$typeInfo['examples']}

        ### TONO Y ESTILO: {$toneInfo['description']}
        - **Técnicas de redacción**: {$toneInfo['techniques']}
        - **Vocabulario recomendado**: {$toneInfo['vocabulary']}
        - **Elementos a evitar**: {$toneInfo['avoid']}

        ### EXTENSIÓN: {$lengthInfo['words']}
        - **Directrices de longitud**: {$lengthInfo['guidelines']}
        - **Estructura sugerida**: {$lengthInfo['structure']}

        ## BRIEF DEL PROYECTO
        **Descripción detallada del contenido solicitado:**
        {$description}

        ## INSTRUCCIONES DE EJECUCIÓN

        ### CALIDAD Y PRECISIÓN
        1. **Fidelidad al brief**: Cada elemento debe responder directamente a los requerimientos especificados
        2. **Consistencia tonal**: Mantén el tono {$toneInfo['description']} de manera uniforme
        3. **Precisión en extensión**: Respeta estrictamente los parámetros de {$lengthInfo['words']}
        4. **Estructura profesional**: Implementa la estructura específica para {$typeInfo['name']}

        ### ESTÁNDARES DE REDACCIÓN
        - **Claridad**: Cada oración debe ser clara e inequívoca
        - **Fluidez**: Transiciones naturales entre ideas
        - **Impacto**: Cada párrafo debe agregar valor específico
        - **Accionabilidad**: El contenido debe generar la respuesta deseada

        ### FORMATO DE ENTREGA
        - **Contenido puro**: Entrega ÚNICAMENTE el texto solicitado
        - **Sin metadatos**: No incluyas explicaciones, comentarios o justificaciones
        - **Listo para uso**: El texto debe poder utilizarse inmediatamente sin ediciones
        - **Sin elementos estructurales externos**: Evita encabezados como 'Asunto:', 'Para:', etc., a menos que sean parte orgánica del contenido

        ## CRITERIOS DE ÉXITO
        - ✅ Alignment total con el tipo de contenido especificado
        - ✅ Tono consistente y apropiad    o para el contexto
        - ✅ Extensión precisa según parámetros establecidos
        - ✅ Estructura profesional y efectiva
        - ✅ Lenguaje claro y orientado a resultados
        - ✅ Contenido listo para implementación inmediata

        **ENTREGA**: Proporciona únicamente el contenido final solicitado, sin preámbulos, explicaciones o comentarios adicionales.";
    }



    public function translate(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        Log::info("Iniciando traducción");
        try {
            // Obtener la API key desde el archivo .env
            $apiKey = env('IA_API_KEY');

            Log::info("Llegué a la función translate");
            
            if (!$apiKey) {
                Log::error('API Key de Groq no encontrada en las variables de entorno');
                return response()->json([
                    'success' => false,
                    'message' => 'API Key no configurada'
                ], 500);
            }

            // Cambiar de input() a query() para GET
            $fromLang = $request->query('fromLang', 'es');
            $toLang = $request->query('toLang', 'en');
            $text = $request->query('text', '');
            $preserveFormat = $request->query('preserveFormat', true);

            // Log de parámetros recibidos
            Log::info('translate - Parámetros recibidos:', [
                'fromLang' => $fromLang,
                'toLang' => $toLang,
                'text_length' => strlen($text),
                'preserveFormat' => $preserveFormat,
                'text_preview' => substr($text, 0, 100) . '...'
            ]);

        // Validar que se proporcione texto
        if (empty($text)) {
            Log::warning('translate - Texto vacío proporcionado');
            return response()->json([
                'success' => false,
                'message' => 'El texto a traducir es requerido'
            ], 400);
        }

        // Construir el prompt de traducción
        $prompt = $this->buildTranslationPrompt($fromLang, $toLang, $text, $preserveFormat);
        
        Log::info('translate - Prompt construido:', [
            'prompt_length' => strlen($prompt),
            'prompt_preview' => substr($prompt, 0, 200) . '...'
        ]);

        // Realizar petición a Groq AI con GPT-OSS-120B
        Log::info('translate - Iniciando petición a Groq AI');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'openai/gpt-oss-120b',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 3000,
            'temperature' => 0.3, // Temperatura baja para traducción precisa
            'top_p' => 0.8
        ]);

        Log::info('translate - Petición completada:', [
            'status_code' => $response->status(),
            'response_size' => strlen($response->body())
        ]);

        // Verificar si la petición fue exitosa
        if ($response->successful()) {
            Log::info('translate - Respuesta exitosa de Groq AI');
            
            $responseData = $response->json();
            
            // Extraer el contenido traducido
            $translatedText = $responseData['choices'][0]['message']['content'] ?? 'No se pudo extraer la traducción';
            
            // Limpiar el texto traducido
            $cleanText = trim($translatedText);
            
            // Log del texto traducido
            Log::info('translate - Traducción completada:', [
                'fromLang' => $fromLang,
                'toLang' => $toLang,
                'original_length' => strlen($text),
                'translated_length' => strlen($cleanText),
                'translation_preview' => substr($cleanText, 0, 150) . '...'
            ]);

            // Preparar respuesta final
            $finalResponse = [
                'success' => true,
                'translatedText' => $cleanText,
                'result' => $cleanText,
                'metadata' => [
                    'fromLang' => $fromLang,
                    'toLang' => $toLang,
                    'original_char_count' => strlen($text),
                    'translated_char_count' => strlen($cleanText),
                    'original_word_count' => str_word_count($text),
                    'translated_word_count' => str_word_count($cleanText)
                ]
            ];

            Log::info('translate - Respuesta final preparada:', [
                'response_keys' => array_keys($finalResponse),
                'metadata' => $finalResponse['metadata']
            ]);

            // Registrar estadísticas de uso
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $estimatedTokens = $this->tokenController->estimateTokens($cleanText);
            
            $this->recordToolUsage('translate', $estimatedTokens, $responseTime, true);

            return response()->json($finalResponse, 200);

        } else {
            Log::error('translate - Error en la petición a Groq AI:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Registrar estadísticas de error
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $this->recordToolUsage('translate', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

            return response()->json([
                'success' => false,
                'message' => 'Error en la conexión con Groq AI',
                'error' => $response->body()
            ], $response->status());
        }

    } catch (Exception $e) {
        Log::error('translate - Excepción capturada:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Registrar estadísticas de error
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        $this->recordToolUsage('translate', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    private function buildTranslationPrompt($fromLang, $toLang, $text, $preserveFormat)
    {
    // Mapear códigos de idioma a nombres completos
    $languageMap = [
        'es' => 'español',
        'en' => 'inglés',
        'fr' => 'francés',
        'de' => 'alemán',
        'it' => 'italiano',
        'pt' => 'portugués',
        'ja' => 'japonés',
        'zh' => 'chino',
        'ru' => 'ruso',
        'ar' => 'árabe'
        ];

        $fromLanguage = $languageMap[$fromLang] ?? $fromLang;
        $toLanguage = $languageMap[$toLang] ?? $toLang;

        $formatInstruction = $preserveFormat ? 
            "Preserva exactamente el formato original del texto (saltos de línea, espaciado, puntuación especial, etc.)" : 
            "Traduce el contenido adaptando el formato si es necesario para mejorar la legibilidad";

        return "Eres un traductor profesional especializado en traducciones precisas y naturales. Tu tarea es traducir el siguiente texto de {$fromLanguage} a {$toLanguage}.

        IDIOMA ORIGEN: {$fromLanguage}
        IDIOMA DESTINO: {$toLanguage}
        FORMATO: {$formatInstruction}

        TEXTO A TRADUCIR:
        {$text}

        INSTRUCCIONES ESPECÍFICAS:
        1. Traduce ÚNICAMENTE el contenido, sin explicaciones adicionales
        2. Mantén el significado original con la máxima precisión
        3. Usa un lenguaje natural y fluido en {$toLanguage}
        4. {$formatInstruction}
        5. Si encuentras términos técnicos o nombres propios, manténlos apropiadamente
        6. Respeta el tono y estilo del texto original
        7. No agregues comentarios sobre la traducción

        IMPORTANTE: Responde SOLO con la traducción final, sin comentarios adicionales ni explicaciones sobre el proceso de traducción.";
    }



    public function correct(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        try {
            // Obtener la API key desde el archivo .env
            $apiKey = env('IA_API_KEY');

            Log::info("Llegué a la función correct");
            
            if (!$apiKey) {
                Log::error('API Key de Groq no encontrada en las variables de entorno');
                return response()->json([
                    'success' => false,
                    'message' => 'API Key no configurada'
                ], 500);
            }

            // Obtener parámetros del request
            $language = $request->input('language', 'es');
            $text = $request->input('text', '');
            $options = $request->input('options', []);
            
            // Extraer opciones individuales
            $grammar = $options['grammar'] ?? true;
            $spelling = $options['spelling'] ?? true;
            $punctuation = $options['punctuation'] ?? true;
            $style = $options['style'] ?? false;

            // Log de parámetros recibidos
            Log::info('correct - Parámetros recibidos:', [
                'language' => $language,
                'text_length' => strlen($text),
                'grammar' => $grammar,
                'spelling' => $spelling,
                'punctuation' => $punctuation,
                'style' => $style,
                'text_preview' => substr($text, 0, 100) . '...'
            ]);

            // Validar que se proporcione texto
            if (empty($text)) {
                Log::warning('correct - Texto vacío proporcionado');
                return response()->json([
                    'success' => false,
                    'message' => 'El texto a corregir es requerido'
                ], 400);
            }

            // Construir el prompt de corrección
            $prompt = $this->buildCorrectionPrompt($language, $text, $grammar, $spelling, $punctuation, $style);
            
            Log::info('correct - Prompt construido:', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);

            // Realizar petición a Groq AI con GPT-OSS-120B
            Log::info('correct - Iniciando petición a Groq AI');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-120b',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 3500,
                'temperature' => 0.2, // Temperatura muy baja para corrección precisa
                'top_p' => 0.7
            ]);

            Log::info('correct - Petición completada:', [
                'status_code' => $response->status(),
                'response_size' => strlen($response->body())
            ]);

            // Verificar si la petición fue exitosa
            if ($response->successful()) {
                Log::info('correct - Respuesta exitosa de Groq AI');
                
                $responseData = $response->json();
                
                // Extraer el contenido corregido
                $correctedText = $responseData['choices'][0]['message']['content'] ?? 'No se pudo extraer la corrección';
                
                // Limpiar el texto corregido
                $cleanText = trim($correctedText);
                
                // Log del texto corregido
                Log::info('correct - Corrección completada:', [
                    'language' => $language,
                    'original_length' => strlen($text),
                    'corrected_length' => strlen($cleanText),
                    'corrections_applied' => [
                        'grammar' => $grammar,
                        'spelling' => $spelling,
                        'punctuation' => $punctuation,
                        'style' => $style
                    ],
                    'correction_preview' => substr($cleanText, 0, 150) . '...'
                ]);

                // Preparar respuesta final
                $finalResponse = [
                    'success' => true,
                    'correctedText' => $cleanText,
                    'result' => $cleanText,
                    'metadata' => [
                        'language' => $language,
                        'corrections_applied' => [
                            'grammar' => $grammar,
                            'spelling' => $spelling,
                            'punctuation' => $punctuation,
                            'style' => $style
                        ],
                        'original_char_count' => strlen($text),
                        'corrected_char_count' => strlen($cleanText),
                        'original_word_count' => str_word_count($text),
                        'corrected_word_count' => str_word_count($cleanText)
                    ]
                ];

                Log::info('correct - Respuesta final preparada:', [
                    'response_keys' => array_keys($finalResponse),
                    'metadata' => $finalResponse['metadata']
                ]);

                // Registrar estadísticas de uso
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $estimatedTokens = $this->tokenController->estimateTokens($cleanText);
                
                $this->recordToolUsage('correct', $estimatedTokens, $responseTime, true);

                return response()->json($finalResponse, 200);

            } else {
                Log::error('correct - Error en la petición a Groq AI:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Registrar estadísticas de error
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $this->recordToolUsage('correct', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

                return response()->json([
                    'success' => false,
                    'message' => 'Error en la conexión con Groq AI',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (Exception $e) {
            Log::error('correct - Excepción capturada:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Registrar estadísticas de error
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $this->recordToolUsage('correct', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildCorrectionPrompt($language, $text, $grammar, $spelling, $punctuation, $style)
{
    // Mapear códigos de idioma a nombres completos
    $languageMap = [
        'es' => 'español',
        'en' => 'inglés',
        'fr' => 'francés',
        'de' => 'alemán',
        'it' => 'italiano',
        'pt' => 'portugués',
        'ja' => 'japonés',
        'zh' => 'chino',
        'ru' => 'ruso',
        'ar' => 'árabe'
    ];

    $languageName = $languageMap[$language] ?? $language;

    // Construir lista de correcciones a aplicar
    $corrections = [];
    if ($grammar) $corrections[] = 'gramática';
    if ($spelling) $corrections[] = 'ortografía';
    if ($punctuation) $corrections[] = 'puntuación';
    if ($style) $corrections[] = 'estilo y coherencia';

    $correctionsText = implode(', ', $corrections);

    // Instrucciones específicas según el tipo de corrección
    $specificInstructions = [];
    
    if ($grammar) {
        $specificInstructions[] = "- GRAMÁTICA: Corrige concordancia de género y número, tiempos verbales, uso correcto de artículos (el/la/los/las), preposiciones y estructuras sintácticas. Ejemplo: 'estudiante de universidad' → 'estudiante de la universidad'";
    }
    
    if ($spelling) {
        $specificInstructions[] = "- ORTOGRAFÍA: Corrige acentuación, tildes, mayúsculas, separación de palabras y errores de escritura. Ejemplo: 'matematicas' → 'matemáticas', 'aveces' → 'a veces'";
    }
    
    if ($punctuation) {
        $specificInstructions[] = "- PUNTUACIÓN: Añade comas, puntos, signos de interrogación/exclamación. Divide oraciones muy largas en oraciones más cortas y legibles. Estructura el texto en párrafos cuando sea apropiado.";
    }
    
    if ($style) {
        $specificInstructions[] = "- ESTILO: Mejora fluidez, elimina repeticiones innecesarias, varía conectores, organiza el texto en párrafos lógicos y mejora la coherencia general sin cambiar el mensaje.";
    }

    $instructionsText = implode("\n", $specificInstructions);

    // Agregar instrucciones específicas para el idioma español
    $languageSpecificRules = '';
    if ($language === 'es') {
        $languageSpecificRules = "\n\nREGLAS ESPECÍFICAS PARA ESPAÑOL:
    - Usa correctamente los artículos: 'de universidad' → 'de la universidad'
    - Acentúa correctamente: 'matematicas' → 'matemáticas', 'dificil' → 'difícil'
    - Separa palabras: 'aveces' → 'a veces'
    - Usa tildes en pronombres: 'para mi' → 'para mí'
    - Conjuga verbos correctamente: 'tube' → 'tuve'";
        }

        return "Eres un corrector profesional experto en {$languageName} con años de experiencia en edición académica y profesional. Tu misión es mejorar la calidad del texto manteniendo su esencia original.

    IDIOMA: {$languageName}
    CORRECCIONES A APLICAR: {$correctionsText}

    TEXTO A CORREGIR:
    {$text}

    INSTRUCCIONES DETALLADAS:
    {$instructionsText}

    {$languageSpecificRules}

    CRITERIOS DE CALIDAD:
    1. PRECISIÓN: Cada corrección debe ser necesaria y apropiada
    2. NATURALIDAD: El texto debe sonar fluido y natural
    3. COHERENCIA: Mantén consistencia en el registro y tono
    4. ESTRUCTURA: Organiza en párrafos cuando el texto sea largo (más de 100 palabras)
    5. CONSERVACIÓN: Preserva el significado, intención y personalidad del autor

    FORMATO DE SALIDA:
    - Responde ÚNICAMENTE con el texto corregido
    - NO agregues explicaciones, comentarios o notas
    - NO uses comillas, asteriscos o marcadores especiales
    - Mantén la estructura original si es apropiada, o mejórala si es necesario

    EJEMPLOS DE CORRECCIÓN ESPERADA:
    ❌ 'hola soy estudiante de universidad' 
    ✅ 'Hola, soy estudiante de la universidad'

    ❌ 'tube un examen dificil pero lo hice bien espero sacar buena nota'
    ✅ 'Tuve un examen difícil, pero lo hice bien. Espero sacar una buena nota'

    IMPORTANTE: Tu objetivo es que el texto resultante sea impecable en {$languageName}, manteniendo la voz original del autor.";
    }
    

    public function improve(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        try {
            // Obtener la API key desde el archivo .env
            $apiKey = env('IA_API_KEY');

            Log::info("Llegué a la función improve");
            
            if (!$apiKey) {
                Log::error('API Key de Groq no encontrada en las variables de entorno');
                return response()->json([
                    'success' => false,
                    'message' => 'API Key no configurada'
                ], 500);
            }

            // Obtener parámetros del request
            $type = $request->input('type', 'clarity');
            $text = $request->input('text', '');
            $objective = $request->input('objective', '');

            // Log de parámetros recibidos
            Log::info('improve - Parámetros recibidos:', [
                'type' => $type,
                'text_length' => strlen($text),
                'objective' => $objective,
                'text_preview' => substr($text, 0, 100) . '...'
            ]);

            // Validar que se proporcione texto
            if (empty($text)) {
                Log::warning('improve - Texto vacío proporcionado');
                return response()->json([
                    'success' => false,
                    'message' => 'El texto a mejorar es requerido'
                ], 400);
            }

            // Construir el prompt de mejora
            $prompt = $this->buildImprovementPrompt($type, $text, $objective);
            
            Log::info('improve - Prompt construido:', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);

            // Realizar petición a Groq AI con GPT-OSS-120B
            Log::info('improve - Iniciando petición a Groq AI');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-120b',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 4000,
                'temperature' => 0.6, // Equilibrio entre creatividad y consistencia
                'top_p' => 0.9
            ]);

            Log::info('improve - Petición completada:', [
                'status_code' => $response->status(),
                'response_size' => strlen($response->body())
            ]);

            // Verificar si la petición fue exitosa
            if ($response->successful()) {
                Log::info('improve - Respuesta exitosa de Groq AI');
                
                $responseData = $response->json();
                
                // Extraer el contenido mejorado
                $improvedText = $responseData['choices'][0]['message']['content'] ?? 'No se pudo extraer la mejora';
                
                // Limpiar el texto mejorado
                $cleanText = trim($improvedText);
                
                // Log del texto mejorado
                Log::info('improve - Mejora completada:', [
                    'type' => $type,
                    'original_length' => strlen($text),
                    'improved_length' => strlen($cleanText),
                    'has_objective' => !empty($objective),
                    'improvement_preview' => substr($cleanText, 0, 150) . '...'
                ]);

                // Preparar respuesta final
                $finalResponse = [
                    'success' => true,
                    'improvedText' => $cleanText,
                    'result' => $cleanText,
                    'metadata' => [
                        'improvement_type' => $type,
                        'objective' => $objective,
                        'original_char_count' => strlen($text),
                        'improved_char_count' => strlen($cleanText),
                        'original_word_count' => str_word_count($text),
                        'improved_word_count' => str_word_count($cleanText),
                        'improvement_ratio' => round((strlen($cleanText) / strlen($text)) * 100, 2)
                    ]
                ];

                Log::info('improve - Respuesta final preparada:', [
                    'response_keys' => array_keys($finalResponse),
                    'metadata' => $finalResponse['metadata']
                ]);

                // Registrar estadísticas de uso
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $estimatedTokens = $this->tokenController->estimateTokens($cleanText);
                
                $this->recordToolUsage('improve', $estimatedTokens, $responseTime, true);

                return response()->json($finalResponse, 200);

            } else {
                Log::error('improve - Error en la petición a Groq AI:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Registrar estadísticas de error
                $endTime = microtime(true);
                $responseTime = $endTime - $startTime;
                $this->recordToolUsage('improve', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

                return response()->json([
                    'success' => false,
                    'message' => 'Error en la conexión con Groq AI',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (Exception $e) {
            Log::error('improve - Excepción capturada:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Registrar estadísticas de error
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $this->recordToolUsage('improve', ['estimated_tokens' => 0, 'characters' => 0, 'words' => 0], $responseTime, false);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        private function buildImprovementPrompt($type, $text, $objective)
        {
            // Mapear tipos de mejora a descripciones específicas y estrategias
            $improvementStrategies = [
                'clarity' => [
                    'name' => 'Claridad y Coherencia',
                    'focus' => 'Hacer el texto más claro, directo y fácil de entender',
                    'actions' => [
                        'Simplifica oraciones complejas y largas',
                        'Elimina ambigüedades y términos confusos',
                        'Mejora la lógica y el flujo de ideas',
                        'Asegura conexiones claras entre párrafos',
                        'Usa ejemplos concretos cuando sea apropiado'
                    ]
                ],
                'tone' => [
                    'name' => 'Tono y Estilo',
                    'focus' => 'Optimizar el tono para el público objetivo y propósito',
                    'actions' => [
                        'Ajusta el registro formal/informal según el contexto',
                        'Mejora la voz narrativa y personalidad del texto',
                        'Optimiza el tono emocional (profesional, persuasivo, amigable)',
                        'Asegura consistencia de voz en todo el texto',
                        'Adapta el estilo al medio y audiencia'
                    ]
                ],
                'structure' => [
                    'name' => 'Estructura y Organización',
                    'focus' => 'Reorganizar para máximo impacto y comprensión',
                    'actions' => [
                        'Reorganiza la información en orden lógico',
                        'Crea transiciones fluidas entre secciones',
                        'Mejora la jerarquía de información (títulos, subtítulos)',
                        'Optimiza párrafos para legibilidad',
                        'Estructura para máximo impacto en el público objetivo'
                    ]
                ],
                'vocabulary' => [
                    'name' => 'Vocabulario y Expresión',
                    'focus' => 'Enriquecer el vocabulario y mejorar la expresión',
                    'actions' => [
                        'Reemplaza palabras comunes por términos más precisos',
                        'Elimina repeticiones innecesarias',
                        'Usa sinónimos para crear variedad',
                        'Incorpora términos técnicos apropiados cuando sea relevante',
                        'Mejora la expresividad y riqueza del lenguaje'
                    ]
                ],
                'persuasion' => [
                    'name' => 'Persuasión e Impacto',
                    'focus' => 'Maximizar el poder persuasivo y el impacto',
                    'actions' => [
                        'Fortalece argumentos con evidencia y ejemplos',
                        'Usa técnicas retóricas efectivas',
                        'Crea llamadas a la acción convincentes',
                        'Optimiza el inicio y cierre para máximo impacto',
                        'Incorpora elementos emocionales estratégicamente'
                    ]
                ],
                'concision' => [
                    'name' => 'Concisión',
                    'focus' => 'Comunicar más con menos palabras',
                    'actions' => [
                        'Elimina redundancias y palabras innecesarias',
                        'Condensa ideas sin perder significado',
                        'Usa construcciones más directas y activas',
                        'Combina oraciones relacionadas',
                        'Mantén solo la información esencial'
                    ]
                ],
                'fluency' => [
                    'name' => 'Fluidez',
                    'focus' => 'Crear un texto que fluya naturalmente',
                    'actions' => [
                        'Mejora el ritmo y cadencia del texto',
                        'Varía la longitud de oraciones para crear dinamismo',
                        'Perfecciona las transiciones entre ideas',
                        'Elimina obstáculos en la lectura',
                        'Crea un flujo narrativo envolvente'
                    ]
                ]
            ];

            $strategy = $improvementStrategies[$type] ?? $improvementStrategies['clarity'];
            $actionsText = implode("\n• ", $strategy['actions']);
            
            // Construir objetivo personalizado
            $objectiveText = '';
            if (!empty($objective)) {
                $objectiveText = "\n\nOBJETIVO ESPECÍFICO DEL USUARIO:\n{$objective}\n\nAdapta la mejora para cumplir específicamente con este objetivo.";
            }

            return "Eres un editor profesional especializado en {$strategy['name']}. Tu misión es transformar el texto proporcionado para {$strategy['focus']}.

        TIPO DE MEJORA: {$strategy['name']}
        ENFOQUE PRINCIPAL: {$strategy['focus']}

        TEXTO ORIGINAL A MEJORAR:
        {$text}

        {$objectiveText}

        ESTRATEGIAS ESPECÍFICAS A APLICAR:
        - {$actionsText}

        INSTRUCCIONES DETALLADAS:
        1. ANALIZA el texto identificando oportunidades específicas de mejora en {$strategy['name']}
        2. TRANSFORMA el contenido aplicando las estrategias mencionadas
        3. MANTÉN el significado y mensaje core del texto original
        4. ASEGURA que la mejora sea sustancial y notable
        5. OPTIMIZA para máxima efectividad según el tipo de mejora solicitado
        6. CONSERVA el tono general apropiado para el contexto

        CRITERIOS DE CALIDAD:
        ✓ La mejora debe ser evidente y significativa
        ✓ El texto resultante debe ser superior al original
        ✓ Mantener la autenticidad y voz del autor
        ✓ Adaptar al público y propósito implícito
        ✓ Aplicar mejor práctica editorial profesional

        FORMATO DE SALIDA:
        Responde ÚNICAMENTE con el texto mejorado, sin explicaciones adicionales, comentarios o notas sobre los cambios realizados.

        IMPORTANTE: El resultado debe ser un texto notablemente mejorado que demuestre claramente las optimizaciones en {$strategy['name']}.";
    }

    private function recordToolUsage($tool, $estimatedTokens, $responseTime, $success)
    {
        try {
            $request = new Request([
                'tool' => $tool,
                'tokens' => $estimatedTokens['estimated_tokens'] ?? 0,
                'characters' => $estimatedTokens['characters'] ?? 0,
                'words' => $estimatedTokens['words'] ?? 0,
                'response_time' => $responseTime,
                'success' => $success
            ]);

            $this->tokenController->recordUsage($request);
        } catch (Exception $e) {
            Log::error('IAController::recordToolUsage - Error registrando estadísticas:', [
                'tool' => $tool,
                'error' => $e->getMessage()
            ]);
        }
    }
}
