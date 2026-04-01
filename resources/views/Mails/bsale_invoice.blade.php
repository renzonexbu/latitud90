<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta Electrónica - {{ $company_name }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .email-wrapper { width: 100%; background-color: #f8f9fa; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 126, 147, 0.1); }
        .header { background: linear-gradient(135deg, #007E93 0%, #005f6b 100%); padding: 30px 20px; text-align: center; }
        .logo { max-width: 180px; height: auto; margin-bottom: 15px; background-color: #ffffff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .subject { color: #ffffff; font-size: 22px; font-weight: 600; margin: 0; }
        .content { padding: 30px; }
        .greeting { font-size: 20px; font-weight: bold; color: #007E93; margin-bottom: 20px; }
        .message { font-size: 16px; line-height: 1.8; margin-bottom: 30px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #007E93; border-radius: 0 8px 8px 0; padding: 20px 25px; margin: 25px 0; }
        .info-box h3 { color: #007E93; margin-top: 0; margin-bottom: 15px; font-size: 17px; font-weight: 700; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e9ecef; }
        .detail-row:last-child { border-bottom: none; margin-bottom: 0; }
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #333; }
        .footer { background-color: #007E93; color: #ffffff; text-align: center; padding: 25px 20px; font-size: 13px; }
        .footer p { margin: 5px 0; opacity: 0.9; }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-container">

        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $company_name }}" class="logo">
            <h1 class="subject">Boleta Electrónica</h1>
        </div>

        <div class="content">
            <p class="greeting">Hola, {{ $customer_name }}</p>

            <p class="message">
                Adjunto a este correo encontrarás tu <strong>boleta electrónica</strong> correspondiente al pago realizado en el programa <strong>{{ $program_name }}</strong>.
            </p>

            <div class="info-box">
                <h3>Detalle del documento</h3>
                <div class="detail-row">
                    <span class="detail-label">Programa</span>
                    <span class="detail-value">{{ $program_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">N° Boleta BSale</span>
                    <span class="detail-value">{{ $bsale_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">N° Orden</span>
                    <span class="detail-value">{{ $order_number }}</span>
                </div>
            </div>

            <p class="message">
                Si tienes alguna consulta sobre tu boleta, no dudes en contactarnos.
            </p>
        </div>

        <div class="footer">
            <p><strong>{{ $company_name }}</strong></p>
            @if($company_email)
                <p>{{ $company_email }}</p>
            @endif
            @if($company_phone)
                <p>{{ $company_phone }}</p>
            @endif
        </div>

    </div>
</div>
</body>
</html>
