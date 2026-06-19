<?php
require 'db.php';

$transaction_id = $_GET['transaction_id'] ?? 0;
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

$data = null;
$details = [];
$mode = ''; // 'transaction' or 'single_payment'

if ($transaction_id) {
    $mode = 'transaction';
    // Fetch Transaction Data
    $stmt = $pdo->prepare("
        SELECT 
            t.id as receipt_id,
            t.payment_date as date,
            t.total_amount,
            l.id as loan_id,
            l.amount as loan_amount,
            c.name as client_name,
            c.cedula,
            c.phone as client_phone,
            c.address as client_address
        FROM transactions t
        JOIN loans l ON t.loan_id = l.id
        JOIN clients c ON l.client_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $data = $stmt->fetch();

    if ($data) {
        // Fetch Details
        $stmt_det = $pdo->prepare("
            SELECT 
                td.*,
                p.due_date,
                p.amount_due
            FROM transaction_details td
            JOIN payments p ON td.payment_id = p.id
            WHERE td.transaction_id = ?
        ");
        $stmt_det->execute([$transaction_id]);
        $details = $stmt_det->fetchAll();
    }

} elseif ($payment_id) {
    $mode = 'single_payment';
    // Fetch Single Payment Data (Legacy Mode)
    $stmt = $pdo->prepare("
        SELECT 
            p.id as receipt_id,
            p.paid_date as date,
            p.paid_amount,
            p.late_fee,
            p.amount_due,
            p.due_date,
            l.id as loan_id,
            l.amount as loan_amount,
            l.total_amount as loan_total, -- Added to track balances
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

    if ($data) {
        // Calculate total paid for this specific receipt logic
        // Note: In legacy mode, we treated the payment row as the receipt. 
        // We will simulate the 'total_amount' for consistency
        $data['total_amount'] = $data['paid_amount'] + ($data['late_fee'] ?? 0); // Assuming late_fee is fully paid if present in this context? 
        // Actually, existing code used paid_amount + late_fee (if any added).
        // Let's stick to what was there: $total_paid = $data['paid_amount'] + ($data['late_fee'] ?? 0);
    }
}

if (!$data) {
    die("Recibo no encontrado");
}

// Calculate Saldo Restante (Global Loan Balance)
if ($mode == 'transaction') {
    // Get Loan Total
    $stmt_loan = $pdo->prepare("SELECT total_amount FROM loans WHERE id = ?");
    $stmt_loan->execute([$data['loan_id']]);
    $loan_total = $stmt_loan->fetchColumn() ?: 0;

    // Sum of all capital payments made UP TO this transaction ID
    $stmt_bal = $pdo->prepare("
        SELECT SUM(td.amount_applied) 
        FROM transaction_details td
        JOIN transactions t ON td.transaction_id = t.id
        WHERE t.loan_id = ? AND t.id <= ? AND td.type = 'capital'
    ");
    $stmt_bal->execute([$data['loan_id'], $transaction_id]);
    $total_paid_up_to_now = $stmt_bal->fetchColumn() ?: 0;

    // Capital applied in THIS transaction
    $stmt_cap = $pdo->prepare("SELECT SUM(amount_applied) FROM transaction_details WHERE transaction_id = ? AND type = 'capital'");
    $stmt_cap->execute([$transaction_id]);
    $capital_applied = $stmt_cap->fetchColumn() ?: 0;

    $saldo_restante = max(0, $loan_total - $total_paid_up_to_now);
    $saldo_inicial = $saldo_restante + $capital_applied;
} else {
    // Logic for legacy single payment mode
    $stmt_history = $pdo->prepare("SELECT SUM(paid_amount) as total_paid_so_far FROM payments WHERE loan_id = ? AND id <= ?");
    $stmt_history->execute([$data['loan_id'], $payment_id]);
    $history = $stmt_history->fetch();
    $total_paid_so_far = $history['total_paid_so_far'] ?? 0;

    $saldo_restante = $data['loan_total'] - $total_paid_so_far;
    $saldo_inicial = $saldo_restante + $data['paid_amount'];

    $saldo_restante = max(0, $saldo_restante);
    $saldo_inicial = max(0, $saldo_inicial);
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #<?= $data['receipt_id'] ?></title>
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

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin: 10px 0;
        }

        .details-table th,
        .details-table td {
            text-align: left;
            padding: 4px 0;
        }

        .details-table td.amount {
            text-align: right;
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
            <span><strong>#<?= str_pad($data['receipt_id'], 6, '0', STR_PAD_LEFT) ?></strong></span>
        </div>

        <div class="row">
            <span>Fecha:</span>
            <span><?= date('d/m/Y', strtotime($data['date'])) ?></span>
        </div>

        <div class="row">
            <span>Hora:</span>
            <span><?= date('h:i A', strtotime($data['date'])) ?></span>
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

        <?php if ($mode == 'transaction'): ?>
            <p style="margin: 5px 0; font-weight: bold;">Detalle de Pago:</p>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Concepto / Venc.</th>
                        <th class="amount">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $det):
                        $type_label = ($det['type'] == 'late_fee') ? 'Mora' : 'Cuota';
                        $date_label = date('d/m/Y', strtotime($det['due_date']));
                        ?>
                        <tr>
                            <td><?= $type_label ?> (<?= $date_label ?>)</td>
                            <td class="amount"><?= $currency . number_format($det['amount_applied'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="row">
                <span>Concepto:</span>
                <span>Pago de Cuota</span>
            </div>
            <div class="row">
                <span>Fecha Vencimiento:</span>
                <span><?= date('d/m/Y', strtotime($data['due_date'])) ?></span>
            </div>

            <div class="row">
                <span>Monto Cuota:</span>
                <span><?= $currency ?><?= number_format($data['amount_due'], 2) ?></span>
            </div>
            <?php if (isset($data['late_fee']) && $data['late_fee'] > 0): ?>
                <div class="row" style="color: #dc2626;">
                    <span>Mora/Recargo:</span>
                    <span><?= $currency ?><?= number_format($data['late_fee'], 2) ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <hr style="border: none; border-top: 1px dashed #333; margin: 15px 0;">

        <?php if ($mode == 'transaction'): ?>
            <div class="row">
                <span>Saldo Anterior (Aprox):</span>
                <span><?= $currency ?><?= number_format($saldo_inicial, 2) ?></span>
            </div>
        <?php else: ?>
            <div class="row">
                <span>Saldo Inicial:</span>
                <span><?= $currency ?><?= number_format($saldo_inicial, 2) ?></span>
            </div>
        <?php endif; ?>

        <div class="row total">
            <span>TOTAL PAGADO:</span>
            <span><?= $currency ?><?= number_format($data['total_amount'], 2) ?></span>
        </div>

        <div class="row">
            <span>Saldo Restante:</span>
            <span><?= $currency ?><?= number_format($saldo_restante, 2) ?></span>
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

    <a href='loan_details.php?id=<?= $data['loan_id'] ?>' class='btn-print'
        style='background: #10b981; margin-top: 10px; text-decoration: none;'>← Volver al Préstamo</a>
</body>

</html>