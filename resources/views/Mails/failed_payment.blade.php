<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latitud 90 - Cobro Rechazado</title>
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
        .alert-box {
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 2px 8px rgba(255, 178, 50, 0.2);
        }
        .alert-label {
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .alert-box p {
            color: #ffffff;
            margin: 0;
            font-size: 16px;
            line-height: 1.5;
        }
        .payment-details {
            background-color: #f8f9fa;
            border-left: 4px solid #FFB232;
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
            color: #FFB232;
        }
        .action-section {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }
        .action-title {
            font-weight: 700;
            color: #856404;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .action-text {
            color: #856404;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #007E93 0%, #005f6b 100%);
            color: #ffffff !important;
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 16px;
            margin: 10px 0;
            box-shadow: 0 4px 15px rgba(0, 126, 147, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 126, 147, 0.4);
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
        .footer a {
            color: #ffffff;
            text-decoration: none;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <img src="{{ $message->embedData(file_get_contents(public_path('images/logo-color.png')), 'logo.png', 'image/png') }}" alt="Latitud 90" class="logo">
                <div class="subject">Cobro Rechazado - Acción Requerida</div>
            </div>

            <div class="content">
                <div class="greeting">
                    Estimado apoderado,
                </div>

                <div class="alert-box">
                    <div class="alert-label">⚠️ IMPORTANTE - COBRO RECHAZADO</div>
                    <p>Informamos que el cobro automático de la cuota {{ $installment_number }} para {{ $participant_name }} ha sido rechazado.</p>
                </div>

                <div class="payment-details">
                    <div class="detail-item">
                        <strong>Participante:</strong> {{ $participant_name }}
                    </div>
                    <div class="detail-item">
                        <strong>Programa:</strong> {{ $program_name }}
                    </div>
                    <div class="detail-item">
                        <strong>Cuota rechazada:</strong> Cuota {{ $installment_number }}
                    </div>
                    <div class="detail-item">
                        <strong>Monto:</strong> ${{ $amount }} CLP
                    </div>
                    <div class="detail-item">
                        <strong>Fecha de intento:</strong> {{ $charge_date }}
                    </div>
                </div>

                <div class="action-section">
                    <div class="action-title">¿Qué necesitas hacer?</div>
                    <div class="action-text">
                        Para continuar con el pago de las cuotas de forma automática, es necesario que actualices tu tarjeta de crédito o débito.
                    </div>
                    <a href="{{ $card_update_link }}" class="cta-button">
                        Actualizar Tarjeta
                    </a>
                    <p style="margin-top: 15px; color: #856404; font-size: 13px;">
                        Este enlace es seguro y te llevará al portal de VirtualPOS para actualizar tu método de pago.
                    </p>
                </div>

                <p style="color: #666; font-size: 14px; line-height: 1.8;">
                    <strong>Nota:</strong> Si el problema persiste después de actualizar tu tarjeta, te recomendamos contactar a tu entidad bancaria para verificar que no haya restricciones en tu tarjeta para pagos recurrentes.
                </p>

                <div class="security-notice">
                    <div class="security-title">🔒 Correo seguro</div>
                    <ul class="security-list">
                        <li>Nunca solicitaremos tus claves, números de tarjeta, por teléfono o correo electrónico</li>
                        <li>No debes abrir o descargar archivos de remitentes desconocidos</li>
                        <li>Nunca te solicitaremos pagar directamente por un email</li>
                    </ul>
                </div>

                <!-- Contact Info Section -->
                <div style="background-color: #f8f9fa; padding: 20px; margin: 25px 0; border-radius: 8px; text-align: center;">
                    <p style="margin: 8px 0; font-size: 14px; color: #555;">
                        Cualquier consulta sobre Portal de Pago, favor escribir a: <a href="mailto:pagos@latitud90.com" style="color: #007E93;">pagos@latitud90.com</a>
                    </p>
                    <p style="margin: 8px 0; font-size: 14px; color: #555;">
                        Cualquier consulta por detalles comerciales u otro, favor escribir a: <a href="mailto:contacto@latitud90.com" style="color: #007E93;">contacto@latitud90.com</a>
                    </p>
                </div>
            </div>

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
