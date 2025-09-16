<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago - {{ $company_name }}</title>
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
        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        .success-icon .circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
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
            margin-bottom: 30px;
        }
        .program-info {
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 2px 8px rgba(255, 178, 50, 0.2);
        }
        .program-info h3 {
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 700;
        }
        .program-info p {
            color: #ffffff;
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .payment-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007E93;
            border-radius: 0 8px 8px 0;
            padding: 25px;
            margin: 25px 0;
        }
        .payment-details h3 {
            color: #007E93;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
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
        .contact-info {
            margin-top: 20px;
        }
        .contact-info a {
            color: #ffffff;
            text-decoration: none;
            opacity: 0.9;
        }
        .contact-info a:hover {
            opacity: 1;
            text-decoration: underline;
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
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                box-shadow: none;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-value {
                margin-top: 5px;
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
                <div class="subject">¡Pago Confirmado!</div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Success Icon -->
                <div class="success-icon">
                    <div class="circle">✓</div>
                </div>

                <!-- Greeting -->
                <div class="greeting">
                    Hola {{ $customer_name }},
                </div>

                <!-- Message -->
                <div class="message">
                    <p>¡Excelente! Tu pago ha sido procesado exitosamente. A continuación encontrarás los detalles de tu transacción.</p>
                </div>

                <!-- Program Info -->
                <div class="program-info">
                    <h3>{{ $program_name }}</h3>
                    @if($program_description)
                        <p>{{ $program_description }}</p>
                    @endif
                </div>

                <!-- Payment Details -->
                <div class="payment-details">
                    <h3>Detalles del Pago</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Número de Orden:</span>
                        <span class="detail-value">{{ $order_number }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Monto Pagado:</span>
                        <span class="detail-value amount">${{ $payment_amount }} {{ $payment_currency }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Método de Pago:</span>
                        <span class="detail-value">{{ $payment_method }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Fecha de Pago:</span>
                        <span class="detail-value">{{ $payment_date }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">ID de Transacción:</span>
                        <span class="detail-value">{{ $transaction_id }}</span>
                    </div>

                    @if($total_installments > 1)
                    <div class="detail-row">
                        <span class="detail-label">Cuota:</span>
                        <span class="detail-value">{{ $installment_number }} de {{ $total_installments }}</span>
                    </div>
                    @endif
                </div>

                <!-- Additional Message -->
                <div class="message">
                    <p>Gracias por confiar en {{ $company_name }}. Pronto recibirás más información sobre tu programa.</p>
                    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>{{ $company_name }}</strong></p>
                <div class="contact-info">
                    <p>📧 <a href="mailto:{{ $company_email }}">{{ $company_email }}</a></p>
                    <p>📞 <a href="tel:{{ $company_phone }}">{{ $company_phone }}</a></p>
                </div>
                <p style="margin-top: 20px; font-size: 12px; opacity: 0.8;">
                    Este es un email automático, por favor no respondas a este mensaje.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
