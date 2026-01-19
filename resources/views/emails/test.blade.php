<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .success-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #4F46E5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #777;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $testData['app_name'] }}</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Prueba de Configuración de Email</p>
        </div>

        <div class="success-icon">
            ✓
        </div>

        <div style="text-align: center; margin-bottom: 30px;">
            <span class="status-badge">Email Funcionando Correctamente</span>
        </div>

        <p style="font-size: 16px; color: #555;">
            <strong>¡Enhorabuena!</strong> Si estás leyendo este mensaje, significa que la configuración de email de tu aplicación está funcionando correctamente.
        </p>

        <div class="info-box">
            <h3 style="margin-top: 0; color: #4F46E5;">Información de la Configuración</h3>

            <div class="info-row">
                <span class="info-label">Aplicación:</span>
                <span class="info-value">{{ $testData['app_name'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">URL:</span>
                <span class="info-value">{{ $testData['app_url'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Entorno:</span>
                <span class="info-value">{{ $testData['app_env'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Driver de Email:</span>
                <span class="info-value">{{ $testData['mail_driver'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Host SMTP:</span>
                <span class="info-value">{{ $testData['mail_host'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Enviado desde:</span>
                <span class="info-value">{{ $testData['mail_from'] }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Fecha y hora:</span>
                <span class="info-value">{{ $testData['sent_at'] }}</span>
            </div>
        </div>

        <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; color: #92400e;">
                <strong>Nota:</strong> Este es un correo de prueba generado automáticamente por el comando <code>php artisan email:test</code>
            </p>
        </div>

        <div class="footer">
            <p style="margin: 5px 0;">Este mensaje fue enviado automáticamente por {{ $testData['app_name'] }}</p>
            <p style="margin: 5px 0;">Por favor, no respondas a este correo.</p>
        </div>
    </div>
</body>
</html>
