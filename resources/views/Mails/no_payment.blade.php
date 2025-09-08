<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latitud 90 - Aviso situación Portal Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        .subject {
            color: #2c5aa0;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .important-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-label {
            font-weight: bold;
            color: #856404;
            text-transform: uppercase;
        }
        .program-details {
            background-color: #f8f9fa;
            border-left: 4px solid #2c5aa0;
            padding: 15px;
            margin: 20px 0;
        }
        .detail-item {
            margin: 10px 0;
            font-weight: bold;
        }
        .security-notice {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 20px;
            margin: 30px 0;
        }
        .security-title {
            font-weight: bold;
            color: #0c5460;
            margin-bottom: 15px;
            text-align: center;
        }
        .security-list {
            list-style: none;
            padding: 0;
        }
        .security-list li {
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        .security-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <!-- Logo placeholder - will be replaced with actual logo -->
            <img src="{{ asset('images/logo-latitud90.png') }}" alt="Latitud 90" class="logo">
        </div>

        <div class="subject">
            Latitud 90, Aviso situación Portal Pago "Programa educativo"
        </div>

        <div class="greeting">
            Hola
        </div>

        <div class="important-notice">
            <div class="important-label">IMPORTANTE:</div>
            <p>Informamos que presenta cuotas pendientes de pago o un registro incompleto en nuestra tienda online. Solicitamos regularizar a la brevedad para resguardar el cupo del alumno/a en el programa.</p>
        </div>

        <div class="program-details">
            <div class="detail-item">
                Fecha de incorporación al portal Pago del alumno: "xx"
            </div>
            <div class="detail-item">
                Monto Programa educativo presupuestado: "xx"
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

        <div class="footer">
            <p>Este es un correo automático, por favor no responder.</p>
            <p>&copy; {{ date('Y') }} Latitud 90. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>