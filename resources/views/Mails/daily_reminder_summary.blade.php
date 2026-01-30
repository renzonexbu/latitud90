<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latitud 90 - Resumen Diario de Recordatorios</title>
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
            max-width: 95%;
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
        .date-badge {
            display: inline-block;
            background-color: rgba(255,255,255,0.2);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #007E93;
        }
        .stat-card.highlight {
            border-left-color: #FFB232;
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cc 100%);
        }
        .stat-card.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        .stat-card.info {
            border-left-color: #17a2b8;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #007E93;
            margin: 0;
        }
        .stat-card.highlight .stat-number {
            color: #ff9f00;
        }
        .stat-card.success .stat-number {
            color: #28a745;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #007E93;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007E93;
        }
        .reminder-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .reminder-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #007E93;
        }
        .reminder-item:nth-child(odd) {
            border-left-color: #FFB232;
        }
        .participant-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .reminder-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 13px;
        }
        .detail-row {
            color: #555;
        }
        .detail-row strong {
            color: #007E93;
        }
        .reminder-badge {
            display: inline-block;
            background: linear-gradient(135deg, #FFB232 0%, #ff9f00 100%);
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }
        .days-badge {
            display: inline-block;
            background-color: #dc3545;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }
        .data-table th {
            background-color: #007E93;
            color: #ffffff;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .data-table tr:hover {
            background-color: #e3f2fd;
        }
        .no-reminders {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .no-reminders-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .total-amount {
            background: linear-gradient(135deg, #007E93 0%, #005f6b 100%);
            color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .total-amount .amount {
            font-size: 28px;
            font-weight: 700;
        }
        .total-amount .label {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .reminder-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <img src="{{ $message->embedData(file_get_contents(public_path('images/logo-color.png')), 'logo.png', 'image/png') }}" alt="Latitud 90" class="logo">
                <div class="subject">Resumen Diario - Recordatorios de Pago</div>
                <div class="date-badge">{{ $date }}</div>
            </div>

            <div class="content">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-number">{{ $remindersSent }}</div>
                        <div class="stat-label">Enviados hoy</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number">{{ $remindersSkipped }}</div>
                        <div class="stat-label">Sin email apoderado</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-number">{{ $notDueYet }}</div>
                        <div class="stat-label">Fuera de ciclo</div>
                    </div>
                    <div class="stat-card highlight">
                        <div class="stat-number">{{ $totalParticipants }}</div>
                        <div class="stat-label">Total procesados</div>
                    </div>
                </div>

                <!-- Total Amount -->
                @if($totalAmount > 0)
                <div class="total-amount">
                    <div class="amount">${{ number_format($totalAmount, 0, ',', '.') }}</div>
                    <div class="label">Monto total pendiente (recordatorios enviados)</div>
                </div>
                @endif

                <!-- Reminders Table -->
                @if(count($summaryData) > 0)
                <div class="section-title">Detalle de Recordatorios Enviados ({{ count($summaryData) }})</div>
                <table class="data-table" cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Participante</th>
                            <th>Programa</th>
                            <th style="text-align: right;">Monto</th>
                            <th style="text-align: center;">Días</th>
                            <th>Email Apoderado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryData as $data)
                        <tr>
                            <td style="font-size: 11px; font-family: monospace;">{{ $data['enrollment_code'] ?? '-' }}</td>
                            <td>
                                <strong>{{ $data['name'] }}</strong>
                            </td>
                            <td style="font-size: 12px; font-family: monospace;">{{ $data['program_code'] ?? '-' }}</td>
                            <td style="text-align: right; white-space: nowrap;">${{ $data['amount_formatted'] }}</td>
                            <td style="text-align: center;">
                                <span class="days-badge">{{ $data['days_since_enrollment'] }}</span>
                            </td>
                            <td style="font-size: 11px;">{{ $data['guardian_email'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="no-reminders">
                    <div class="no-reminders-icon">📭</div>
                    <p><strong>No hubo recordatorios que enviar hoy</strong></p>
                    <p style="font-size: 14px; color: #888;">Los recordatorios se envían cada 30 días desde la fecha de enrolamiento</p>
                </div>
                @endif
            </div>

            <div class="footer">
                <p><strong>Latitud 90 - Sistema de Pagos</strong></p>
                <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">Este es un correo automático generado por el sistema.</p>
            </div>
        </div>
    </div>
</body>
</html>
