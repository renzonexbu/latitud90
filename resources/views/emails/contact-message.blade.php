<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto - Latitud 90</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007e93;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #007e93;
            margin-bottom: 5px;
        }
        .field-value {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #ffb232;
        }
        .message-content {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #ffb232;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nuevo mensaje de contacto</h1>
        <p>Has recibido un nuevo mensaje desde el formulario de contacto de Latitud 90</p>
    </div>
    
    <div class="content">
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
    </div>
    
    <div class="footer">
        <p>Este mensaje fue enviado desde el formulario de contacto de Latitud 90</p>
        <p>Para responder, simplemente responde a este email y llegará directamente a {{ $email ?? '' }}</p>
    </div>
</body>
</html>
