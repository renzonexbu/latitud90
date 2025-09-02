<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago - {{ $company_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007E93 0%, #005a6b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        .success-icon .circle {
            width: 80px;
            height: 80px;
            background-color: #28a745;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
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
        .payment-details {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .payment-details h3 {
            color: #007E93;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
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
            font-weight: bold;
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
        .program-info {
            background-color: #e8f4fd;
            border-left: 4px solid #007E93;
            padding: 20px;
            margin-bottom: 30px;
        }
        .program-info h3 {
            color: #007E93;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 20px;
        }
        .contact-info a {
            color: #007E93;
            text-decoration: none;
        }
        .contact-info a:hover {
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .container {
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
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>¡Pago Confirmado!</h1>
            <p>{{ $company_name }}</p>
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
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                Este es un email automático, por favor no respondas a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>
