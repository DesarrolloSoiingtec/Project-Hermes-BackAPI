<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subject ?? 'Notificación' }}</title>
    <style>
        body,html{margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;color:#333;line-height:1.5;}
        .wrapper{width:100%;padding:40px 0;}
        .container{max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(16,24,40,0.12);overflow:hidden;}
        .header{background:#23253a;color:#fff;padding:14px 28px;}
        .logo{font-weight:700;font-size:20px;letter-spacing:-0.02em;}
        .content{padding:24px 32px 12px;} /* reducido padding inferior */
        .card{background:#fbfcfd;border-radius:8px;padding:20px;margin-bottom:24px;border:1px solid #e4e7ec;}
        .meta{font-size:14px;color:#475467;margin-bottom:16px;line-height:1.4;}
        .meta strong{color:#101828;font-weight:600;}
        .disclaimer{display:inline-block;background:#fef2f0;color:#b42318;padding:12px 16px;border-radius:6px;font-size:14px;border:1px solid #fecdca;margin:24px 0;}
        .copyright{
            text-align:center;
            font-size:12px;
            color:#667085;
            margin:12px 0 0 0;             /* reducir espacio superior */
            padding:0 32px;
        }
        .confidential{
            text-align:justify;            /* justificar el texto en bloque */
            font-size:12px;
            color:#667085;
            margin:8px 0 12px 0;           /* menos espacio arriba y abajo */
            padding:0 32px;
            line-height:1.4;
            max-width:85%;                 /* ancho mayor y centrado */
            margin-left:auto;
            margin-right:auto;
        }
        a.cta{display:inline-block;padding:12px 20px;background:#ff7a18;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;transition:background-color 0.2s;box-shadow:0 1px 2px rgba(16,24,40,0.05);}
        a.cta:hover{background:#e56b0f;}
        pre{white-space:pre-wrap;word-wrap:break-word;background:transparent;border:0;margin:0;padding:0;font-family:inherit;}
        .cta-container{text-align:center;margin:24px 0;}
        @media (max-width:480px){
            .wrapper{padding:20px 0;}
            .container{margin:0 16px;border-radius:8px;}
            .header{padding:10px 20px;}
            .content{padding:24px;}
            .copyright{padding:0 24px;}
            .confidential{padding:0 24px;}
            .logo{font-size:18px;}
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container" role="article" aria-roledescription="email">
            <div class="header" style="text-align: center;">
                <img src="https://c03517ef83a8.ngrok.app/completo_negro_naranja.png"
                     alt="Logo"
                     class="logo img-fluid"
                     style="width: 180px; height: 75px; object-fit: contain;">
            </div>
            <div class="content">
                <div class="meta">
                    <strong>{{ $subject ?? 'Notificación' }}</strong><br>
                    Enviado: {{ $date ?? now()->format('d/m/Y H:i') }}
                </div>
                <div class="card">
                    {{-- Si el contenido contiene HTML mostramos sin escapar (heurística), si no, escapamos y preservamos saltos de línea --}}
                    @if(!empty($content) && (strpos($content, '<') !== false))
                        {!! $content !!}
                    @else
                        <pre>{!! nl2br(e($content ?? '')) !!}</pre>
                    @endif
                </div>
                @if(!empty($payload['cta_url']) && !empty($payload['cta_text']))
                    <div class="cta-container">
                        <a class="cta" href="{{ $payload['cta_url'] }}">{{ $payload['cta_text'] }}</a>
                    </div>
                @endif
                {{-- <div style="text-align:center;">
                    <span class="disclaimer">
                        Si no esperabas este correo puedes ignorarlo.
                    </span>
                </div> --}}
            </div>
        </div>
    </div>

    <!-- Reemplazamos el bloque con estilos inline por uno limpio y sin caracteres residuales -->
    <div class="confidential" style="max-width: 42%; margin: 0 auto; mt-3;">
        <strong>Aviso de confidencialidad:</strong> Este mensaje y sus anexos son confidenciales y están dirigidos exclusivamente a su(s) destinatario(s). Si usted ha recibido este correo por error, por favor notifíquelo inmediatamente al remitente y elimínelo de su sistema. Queda prohibida cualquier revisión, uso, divulgación o distribución no autorizada de su contenido.
    </div>

    <div class="copyright">
        © 2025 Hermes Mail™ – Todos los derechos reservados.
    </div>
</body>
</html>
