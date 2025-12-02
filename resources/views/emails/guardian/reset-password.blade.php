<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña - Latitud 90</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 126, 147, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #007E93 0%, #005f6b 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 15px;
            background-color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .subject {
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: bold;
            color: #007E93;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            color: #ffffff;
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(255, 178, 50, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 178, 50, 0.4);
        }
        .url-box {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #007E93;
            word-break: break-all;
            margin: 15px 0;
        }
        .url-box a {
            color: #007E93;
            text-decoration: none;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #FFB232;
            border-radius: 0 8px 8px 0;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .warning strong {
            color: #ff9f00;
        }
        .info {
            background-color: #e7f5f7;
            border-left: 4px solid #007E93;
            border-radius: 0 8px 8px 0;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .info strong {
            color: #007E93;
        }
        .footer {
            background-color: #007E93;
            color: #ffffff;
            text-align: center;
            padding: 25px 20px;
            font-size: 13px;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #ffffff;
            text-decoration: none;
            opacity: 0.9;
        }
        .footer a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                box-shadow: none;
            }
            .header, .content, .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <img src="{{ $message->embedData(file_get_contents(public_path('images/logo-color.png')), 'logo.png', 'image/png') }}" alt="Latitud 90" class="logo">
                <div class="subject">Restablecer contraseña</div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Greeting -->
                <div class="greeting">
                    Hola {{ $user->name }},
                </div>

                <!-- Message -->
                <div class="message">
                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Latitud 90.</p>
                </div>

                <!-- CTA Button -->
                <div style="text-align: center;">
                    <a href="{{ $resetUrl }}" class="cta-button">
                        Restablecer mi contraseña
                    </a>
                </div>

                <!-- Alternative URL -->
                <div class="message">
                    <p style="font-size: 14px; color: #555;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                    <div class="url-box">
                        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
                    </div>
                </div>

                <!-- Warning -->
                <div class="warning">
                    <strong>⏰ Importante:</strong> Este enlace expirará en 1 hora por razones de seguridad.
                </div>

                <!-- Info -->
                <div class="info">
                    <strong>🔒 Seguridad:</strong> Si no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura. Tu contraseña actual no será modificada.
                </div>

                <!-- Additional Message -->
                <div class="message">
                    <p style="font-size: 14px; color: #555;">Si tienes problemas o no solicitaste este cambio, por favor contacta a nuestro equipo de soporte de inmediato.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>Latitud 90</strong></p>
                <p>Experiencias educativas inolvidables</p>
                <p style="margin-top: 10px;">
                    <a href="{{ url('/') }}">www.latitud90.com</a>
                </p>
                <p style="margin-top: 20px; font-size: 12px; opacity: 0.8;">
                    Este es un correo automático, por favor no respondas a este mensaje.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
