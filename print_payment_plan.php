<?php
require 'auth.php';
require 'db.php';

if (!isset($_GET['loan_id'])) {
    die("ID de préstamo no especificado.");
}

$loan_id = $_GET['loan_id'];

// Get company settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$company_address = $settings['company_address'] ?? 'Dirección no configurada';
$company_phone = $settings['company_phone'] ?? '';
$currency = $settings['currency_symbol'] ?? '$';
$logo_path = $settings['logo_path'] ?? '';

// Get loan details with client info
$stmt = $pdo->prepare("
    SELECT l.*, c.name as client_name, c.cedula, c.address, c.phone 
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Préstamo no encontrado.");
}

// Get payment schedule
$stmt_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY due_date ASC");
$stmt_payments->execute([$loan_id]);
$payments = $stmt_payments->fetchAll();

// Calculate total paid and pending balance
$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['paid_amount'];
}
$saldo_pendiente = max(0, $loan['total_amount'] - $total_paid);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan de Pagos - <?= htmlspecialchars($loan['client_name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12pt;
            margin: 0 auto;
            max-width: 850px;
            /* Constrain width */
            padding: 40px;
            color: #334155;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .logo {
            max-height: 50px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            text-transform: uppercase;
        }

        .company-info {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }

        .document-title {
            text-align: center;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .document-title h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            border-bottom: 2px solid #3b82f6;
            display: inline-block;
            padding-bottom: 5px;
        }

        .info-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            background: #f8fafc;
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .info-group {
            flex: 1;
        }

        .info-group h3 {
            font-size: 11pt;
            /* Larger header */
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            gap: 10px;
            /* Gap instead of space-between */
            align-items: center;
            margin-bottom: 6px;
            font-size: 11pt;
            /* Larger text */
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            min-width: 100px;
            /* Fixed width for alignment if desired, or auto */
        }

        .info-value {
            font-weight: 700;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 5px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        thead {
            background-color: #f1f5f9;
        }

        th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 10pt;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11pt;
            /* Slightly smaller than body but readable */
            color: #334155;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
            /* Darker stripe for visibility */
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .badge-paid {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #dcfce7;
        }

        .badge-late {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 11pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            body {
                padding: 1.5cm;
                /* Compensate for 0 margin */
                margin: 0;
            }

            .no-print,
            .btn-print {
                display: none !important;
            }
        }

        .btn-print {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            display: block;
            width: fit-content;
            margin: 0 auto 15px;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }

        .btn-print:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>

    <a href="javascript:window.print()" class="btn-print no-print">🖨️ Desea imprimir este documento?</a>

    <div class="header">
        <div style="text-align: left;">
            <?php if (!empty($logo_path)): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="logo">
            <?php endif; ?>
        </div>
        <div style="text-align: left;">
            <h1 class="company-name"><?= htmlspecialchars($company_name) ?></h1>
            <p class="company-info"><?= htmlspecialchars($company_address) ?> | Tel:
                <?= htmlspecialchars($company_phone) ?>
            </p>
        </div>
    </div>


    <div class="document-title">
        <h2>Notificación de Plan de Pagos</h2>
    </div>

    <div style="margin-bottom: 20px; text-align: justify; line-height: 1.5; color: #475569; font-size: 11pt;">
        Estimado(a) cliente, por medio de la presente se le notifica el calendario de pagos correspondiente a su
        crédito.
        Le agradecemos realizar sus abonos puntualmente en las fechas indicadas para mantener un buen historial
        crediticio.
        A continuación se detallan las cuotas programadas:
    </div>

    <div class="info-grid">
        <div class="info-group">
            <h3>Información del Cliente</h3>
            <div class="info-row"><span class="info-label">Nombre:</span> <span
                    class="info-value"><?= htmlspecialchars($loan['client_name']) ?></span></div>
            <div class="info-row"><span class="info-label">Cédula:</span> <span
                    class="info-value"><?= htmlspecialchars($loan['cedula']) ?></span></div>
            <div class="info-row"><span class="info-label">Teléfono:</span> <span
                    class="info-value"><?= htmlspecialchars($loan['phone'] ?? 'N/A') ?></span></div>
        </div>
        <div class="info-group">
            <h3>Detalles del Crédito</h3>
            <div class="info-row"><span class="info-label">Préstamo #:</span> <span
                    class="info-value"><?= $loan['id'] ?></span></div>
            <div class="info-row"><span class="info-label">Total a Pagar:</span> <span class="info-value"
                    style="color: #3b82f6;"><?= $currency . number_format($loan['total_amount'], 2) ?></span></div>
            <div class="info-row"><span class="info-label">Total Pagado:</span> <span class="info-value"
                    style="color: #10b981;"><?= $currency . number_format($total_paid, 2) ?></span></div>
            <div class="info-row"><span class="info-label">Saldo Pendiente:</span> <span class="info-value"
                    style="color: #ef4444;"><?= $currency . number_format($saldo_pendiente, 2) ?></span></div>
            <div class="info-row"><span class="info-label">Frecuencia:</span> <span class="info-value">
                    <?php
                    $freq_map = [
                        'weekly' => 'Semanal',
                        'biweekly' => 'Quincenal',
                        'monthly' => 'Mensual'
                    ];
                    echo $freq_map[strtolower($loan['frequency'])] ?? $loan['frequency'];
                    ?>
                </span></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th class="text-center">Fecha Vencimiento</th>
                <th class="text-center">Monto Cuota</th>
                <th class="text-center">Abonado</th>
                <th class="text-center">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 1;
            $running_balance = $loan['total_amount'];
            foreach ($payments as $payment):
                $balance_due = $payment['amount_due'] - $payment['paid_amount'];
                $running_balance = max(0, $running_balance - $payment['paid_amount']);
                $status_class = 'badge-pending';
                $status_text = 'PENDIENTE';

                if ($payment['status'] == 'paid') {
                    $status_class = 'badge-paid';
                    $status_text = 'PAGADO';
                } elseif ($payment['status'] == 'pending' && strtotime($payment['due_date']) < time()) {
                    $status_class = 'badge-late';
                    $status_text = 'ATRASADO';
                }
                ?>
                <tr>
                    <td class="text-center"><?= $count++ ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($payment['due_date'])) ?></td>
                    <td class="text-center" style="font-weight: 700;">
                        <?= $currency . number_format($payment['amount_due'], 2) ?>
                    </td>
                    <td class="text-center" style="color: #10b981;">
                        <?= $currency . number_format($payment['paid_amount'], 2) ?>
                    </td>
                    <td class="text-center" style="color: #ef4444; font-weight: 700;">
                        <?= $currency . number_format($running_balance, 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Este documento es generado electrónicamente | <?= date('d/m/Y h:i A') ?></p>
        <p><strong><?= htmlspecialchars($company_name) ?></strong> - Su aliado financiero</p>
    </div>

</body>

</html>