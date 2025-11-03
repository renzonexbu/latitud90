<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu cuenta - Latitud90</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        h1 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎒 Latitud90</h1>
        </div>

        <h1>¡Hola {{ $user->name }}!</h1>

        <p>Gracias por registrarte en Latitud90. Para completar tu registro y activar tu cuenta, necesitamos que verifiques tu dirección de correo electrónico.</p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">
                Verificar mi correo electrónico
            </a>
        </div>

        <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
        <p style="background-color: #f3f4f6; padding: 10px; border-radius: 4px; word-break: break-all;">
            <a href="{{ $verificationUrl }}" style="color: #2563eb;">{{ $verificationUrl }}</a>
        </p>

        <div class="warning">
            <strong>⏰ Importante:</strong> Este enlace expirará en 24 horas por razones de seguridad.
        </div>

        <p>Si no creaste esta cuenta, puedes ignorar este correo de forma segura.</p>

        <div class="footer">
            <p>
                <strong>Latitud90</strong><br>
                Experiencias educativas inolvidables<br>
                <a href="{{ url('/') }}" style="color: #2563eb;">www.latitud90.com</a>
            </p>
            <p style="margin-top: 10px;">
                Este es un correo automático, por favor no respondas a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>
