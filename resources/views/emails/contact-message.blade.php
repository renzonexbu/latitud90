<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto - Latitud 90</title>
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
        .field {
            margin-bottom: 20px;
        }
        .field-label {
            font-weight: 600;
            color: #007E93;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .field-value {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 4px solid #FFB232;
            color: #333;
        }
        .message-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #FFB232;
            white-space: pre-wrap;
            line-height: 1.6;
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
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('images/logo-color.png'))) }}" alt="Latitud 90" class="logo">
                <div class="subject">Nuevo mensaje de contacto</div>
            </div>

            <!-- Content -->
            <div class="content">
                <p style="margin-bottom: 25px; color: #555;">Has recibido un nuevo mensaje desde el formulario de contacto de Latitud 90:</p>

                <div class="field">
                    <div class="field-label">Nombre completo:</div>
                    <div class="field-value">{{ $name ?? '' }}</div>
                </div>

                <div class="field">
                    <div class="field-label">Email:</div>
                    <div class="field-value">{{ $email ?? '' }}</div>
                </div>

                <div class="field">
                    <div class="field-label">Teléfono:</div>
                    <div class="field-value">{{ $phone ?? '' }}</div>
                </div>

                <div class="field">
                    <div class="field-label">Mensaje:</div>
                    <div class="message-content">{{ $contactMessage ?? '' }}</div>
                </div>

                <p style="margin-top: 25px; color: #555; font-size: 14px;">
                    Para responder, simplemente responde a este email y llegará directamente a {{ $email ?? '' }}
                </p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>Latitud 90</strong></p>
                <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                    Este mensaje fue enviado desde el formulario de contacto de Latitud 90
                </p>
            </div>
        </div>
    </div>
</body>
</html>
