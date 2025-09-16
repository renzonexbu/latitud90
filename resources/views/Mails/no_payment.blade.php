<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latitud 90 - Aviso situación Portal Pago</title>
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
            font-size: 18px;
            color: #333;
            margin-bottom: 25px;
            font-weight: 500;
        }
        .important-notice {
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 2px 8px rgba(255, 178, 50, 0.2);
        }
        .important-label {
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .important-notice p {
            color: #ffffff;
            margin: 0;
            font-size: 16px;
            line-height: 1.5;
        }
        .program-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007E93;
            border-radius: 0 8px 8px 0;
            padding: 20px;
            margin: 25px 0;
        }
        .detail-item {
            margin: 12px 0;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }
        .detail-item strong {
            color: #007E93;
        }
        .security-notice {
            background-color: #f0f8ff;
            border: 2px solid #007E93;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }
        .security-title {
            font-weight: 700;
            color: #007E93;
            margin-bottom: 15px;
            text-align: center;
            font-size: 16px;
        }
        .security-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .security-list li {
            margin: 12px 0;
            padding-left: 25px;
            position: relative;
            color: #555;
            font-size: 14px;
        }
        .security-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #007E93;
            font-weight: bold;
            font-size: 16px;
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
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            color: #ffffff;
            padding: 12px 30px;
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
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('images/logo-color.png'))) }}" alt="Latitud 90" class="logo">
                <div class="subject">Latitud 90, Aviso situación Portal Pago "Programa educativo"</div>
            </div>

            <div class="content">
                <div class="greeting">
                    Hola
                </div>

                <div class="important-notice">
                    <div class="important-label">IMPORTANTE:</div>
                    <p>Informamos que presenta cuotas pendientes de pago o un registro incompleto en nuestra tienda online. Solicitamos regularizar a la brevedad para resguardar el cupo del alumno/a en el programa.</p>
                </div>

                <div class="program-details">
                    <div class="detail-item">
                        Fecha de incorporación al portal Pago del alumno: "{{ $incorporation_date ?? 'xx' }}"
                    </div>
                    <div class="detail-item">
                        Monto Programa educativo presupuestado: "{{ $program_amount ?? 'xx' }}"
                    </div>
                </div>

                <div class="security-notice">
                    <div class="security-title">Correo seguro</div>
                    <ul class="security-list">
                        <li>Nunca solicitaremos tus claves, números de tarjeta, por teléfono o correo electrónico</li>
                        <li>No debes abrir o descargar archivos de remitentes desconocidos</li>
                        <li>Nunca te solicitaremos pagar directamente por un email</li>
                    </ul>
                </div>
            </div>

            <div class="footer">
                <p><strong>Latitud 90</strong></p>
                <p>Este es un correo automático, por favor no responder.</p>
                <p>&copy; {{ date('Y') }} Latitud 90. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>