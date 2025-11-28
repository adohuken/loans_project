<?php
require 'db.php';

$payment_id = $_GET['payment_id'] ?? 0;

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$currency = $settings['currency_symbol'] ?? '$';
$logo = $settings['logo_path'] ?? '';
$address = $settings['company_address'] ?? '';
$phone = $settings['company_phone'] ?? '';
$footer_msg = $settings['receipt_footer'] ?? '';

// Fetch Payment Data
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        l.amount as loan_amount,
        l.total_amount as loan_total,
        l.interest_rate,
        c.name as client_name,
        c.cedula,
        c.phone as client_phone,
        c.address as client_address
    FROM payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN clients c ON l.client_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Recibo no encontrado");
}

// Calculate total with late fee
$total_paid = $data['paid_amount'] + ($data['late_fee'] ?? 0);
// Calculate Balance History
$stmt_history = $pdo->prepare("SELECT SUM(paid_amount) as total_paid_so_far FROM payments WHERE loan_id = ? AND id <= ?");
$stmt_history->execute([$data['loan_id'], $payment_id]);
$history = $stmt_history->fetch();
$total_paid_so_far = $history['total_paid_so_far'] ?? 0;

$saldo_restante = $data['loan_total'] - $total_paid_so_far;
$saldo_inicial = $saldo_restante + $data['paid_amount'];

// Ensure no negative values (just in case)
$saldo_restante = max(0, $saldo_restante);
$saldo_inicial = max(0, $saldo_inicial);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #<?= $data['id'] ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            background: #f0f0f0;
        }

        .receipt {
            background: white;
            padding: 20px;
            border: 1px dashed #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 1.2rem;
            margin: 5px 0;
        }

        h2 {
            font-size: 1rem;
            margin: 0;
            color: #555;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total {
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
        }

        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #333;
            color: white;
            text-align: center;
            text-decoration: none;
            margin-top: 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .btn-print {
                display: none;
            }

            .receipt {
                border: none;
            }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="header">
            <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h2><?= htmlspecialchars($company_name) ?></h2>
            <?php if ($address): ?>
                <p style="margin: 2px 0; font-size: 0.8rem;"><?= htmlspecialchars($address) ?></p>
            <?php endif; ?>
            <?php if ($phone): ?>
                <p style="margin: 2px 0; font-size: 0.8rem;">Tel: <?= htmlspecialchars($phone) ?></p>
            <?php endif; ?>
            <h1>RECIBO DE PAGO</h1>
        </div>

        <div class="row">
            <span>Recibo No:</span>
            <span><strong>#<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></strong></span>
        </div>

        <div class="row">
            <span>Fecha:</span>
            <span><?= date('d/m/Y', strtotime($data['paid_date'])) ?></span>
        </div>

        <div class="row">
            <span>Hora:</span>
            <span><?= date('h:i A', strtotime($data['paid_date'])) ?></span>
        </div>

        <hr style="border: none; border-top: 1px dashed #333; margin: 15px 0;">

        <div class="row">
            <span>Cliente:</span>
            <span><strong><?= htmlspecialchars($data['client_name']) ?></strong></span>
        </div>

        <?php if ($data['cedula']): ?>
            <div class="row">
                <span>Cédula:</span>
                <span><?= htmlspecialchars($data['cedula']) ?></span>
            </div>
        <?php endif; ?>

        <hr style="border: none; border-top: 1px dashed #333; margin: 15px 0;">

        <div class="row">
            <span>Concepto:</span>
            <span>Pago de Cuota</span>
        </div>

        <div class="row">
            <span>Fecha Vencimiento:</span>
            <span><?= date('d/m/Y', strtotime($data['due_date'])) ?></span>
        </div>

        
        <div class="row">
            <span>Saldo Inicial:</span>
            <span><?= $currency ?><?= number_format($saldo_inicial, 2) ?></span>
        </div>
<div class="row">
            <span>Monto Cuota:</span>
            <span><?= $currency ?><?= number_format($data['amount_due'], 2) ?></span>
        </div>

        <div class="row">
            <span>Monto Pagado:</span>
            <span><?= $currency ?><?= number_format($data['paid_amount'], 2) ?></span>
        </div>

        <div class="row">
            <span>Saldo Restante:</span>
            <span><?= $currency ?><?= number_format($saldo_restante, 2) ?></span>
        </div>

        <?php if (isset($data['late_fee']) && $data['late_fee'] > 0): ?>
            <div class="row" style="color: #dc2626;">
                <span>Mora/Recargo:</span>
                <span><?= $currency ?><?= number_format($data['late_fee'], 2) ?></span>
            </div>
        <?php endif; ?>

        <div class="row total">
            <span>TOTAL RECIBIDO:</span>
            <span><?= $currency ?><?= number_format($total_paid, 2) ?></span>
        </div>

        <div class="footer">
            <?php if ($footer_msg): ?>
                <p><?= nl2br(htmlspecialchars($footer_msg)) ?></p>
            <?php else: ?>
                <p>Gracias por su pago.</p>
            <?php endif; ?>
            <p style="margin-top: 10px; font-size: 0.7rem; color: #888;">
                Este es un comprobante válido de pago
            </p>
        </div>
    </div>

    <button onclick="window.print()" class="btn-print">🖨️ Imprimir Recibo</button>
</body>

</html>    <a href='active_loans.php' class='btn-print' style='background: #10b981; margin-top: 10px; text-decoration: none;'>← Volver a Abonar</a>
