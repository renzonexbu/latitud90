<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenvío de Documento</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2 style="color: #0066cc; margin-top: 0;">{{ $company_name }}</h2>
            <p style="font-size: 16px; margin-bottom: 0;">Reenvío de Documento</p>
        </div>

        <div style="padding: 20px; background-color: #fff; border: 1px solid #dee2e6; border-radius: 5px;">
            <p>Estimado/a <strong>{{ $customer_name }}</strong>,</p>

            <p>
                Adjunto encontrarás el documento solicitado: <strong>{{ $document_type }}</strong>.
            </p>

            <p>
                Este documento fue generado previamente y se encuentra adjunto a este correo electrónico.
            </p>

            <p style="margin-top: 30px;">
                Si tienes alguna consulta, no dudes en contactarnos.
            </p>

            <p>
                Saludos cordiales,<br>
                <strong>{{ $company_name }}</strong>
            </p>
        </div>

        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d; text-align: center;">
            <p style="margin: 0;">Este es un correo automático, por favor no responder.</p>
            <p style="margin: 5px 0 0 0;">© {{ date('Y') }} {{ $company_name }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
