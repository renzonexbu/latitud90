<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido al Portal de Pago Latitud 90</title>
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
        .account-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007E93;
            border-radius: 0 8px 8px 0;
            padding: 25px;
            margin: 25px 0;
        }
        .account-details h3 {
            color: #007E93;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        .account-item {
            margin: 12px 0;
            font-size: 15px;
        }
        .account-item strong {
            color: #007E93;
            display: inline-block;
            min-width: 160px;
        }
        .security-section {
            background-color: #f0f8ff;
            border-left: 4px solid #007E93;
            border-radius: 0 8px 8px 0;
            padding: 20px;
            margin: 25px 0;
        }
        .security-section h3 {
            color: #007E93;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .security-section ul {
            margin: 0;
            padding-left: 20px;
            color: #333;
            line-height: 1.8;
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
                <div class="subject">Bienvenido al Portal de Pago Latitud 90</div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Greeting -->
                <div class="greeting">
                    Estimado(a) {{ ucwords(strtolower($user->name)) }},
                </div>

                <!-- Message -->
                <div class="message">
                    <p>Muchas gracias por registrarte en el portal de pago de Latitud90.</p>
                    <p>Hemos creado una cuenta en nuestro sistema con los siguientes datos:</p>
                </div>

                <!-- Account Details -->
                <div class="account-details">
                    <h3>Datos de tu cuenta</h3>
                    <div class="account-item">
                        <strong>Nombre de usuario:</strong> {{ $user->email }}
                    </div>
                </div>

                <!-- Additional Message -->
                <div class="message">
                    <p>Esperamos que disfrutes la experiencia con Lat. 90.</p>
                    <p>Podrás ver todos los registros de pago con tu usuario en nuestro portal.</p>
                </div>

                <!-- Security Section -->
                <div class="security-section">
                    <h3>🔒 Correo seguro</h3>
                    <ul>
                        <li>Nunca solicitaremos tus claves, números de tarjeta, por teléfono o correo electrónico</li>
                        <li>No debes abrir o descargar archivos de remitentes desconocidos</li>
                        <li>Nunca te solicitaremos pagar directamente por un email</li>
                    </ul>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>Latitud 90</strong></p>
                <div style="margin-top: 15px;">
                    <p>📧 <a href="mailto:contacto@latitud90.com">contacto@latitud90.com</a></p>
                </div>
                <p style="margin-top: 20px; font-size: 12px; opacity: 0.8;">
                    Este es un correo automático, por favor no respondas a este mensaje.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
