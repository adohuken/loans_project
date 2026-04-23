<?php
require 'auth.php';
require 'db.php';

$id = $_GET['id'] ?? 0;

// Fetch Receipt Data
$stmt = $pdo->prepare("SELECT * FROM rent_receipts WHERE id = ?");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Recibo no encontrado");
}

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Mi Empresa';
$currency = $settings['currency_symbol'] ?? '$';
$logo = $settings['logo_path'] ?? '';
$address = $settings['company_address'] ?? '';
$phone = $settings['company_phone'] ?? '';
$footer_msg = $settings['receipt_footer'] ?? 'Gracias por su puntualidad.';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Renta #<?= str_pad($receipt['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            max-width: 500px;
            margin: 0 auto;
            background: #f0f0f0;
        }

        .receipt {
            background: white;
            padding: 30px;
            border: 1px dashed #333;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .logo {
            max-width: 120px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 1.5rem;
            margin: 10px 0;
            letter-spacing: 2px;
        }

        h2 {
            font-size: 1.1rem;
            margin: 0;
            color: #333;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }

        .row:last-child {
            border-bottom: none;
        }

        .label {
            color: #555;
            font-weight: bold;
        }

        .total-box {
            background: #f9f9f9;
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            font-size: 1.4rem;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85rem;
            border-top: 1px dashed #333;
            padding-top: 15px;
        }

        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
        }

        .sig-line {
            border-top: 1px solid #333;
            width: 150px;
            text-align: center;
            padding-top: 5px;
            font-size: 0.8rem;
        }

        .btn-print {
            display: block;
            width: 100%;
            padding: 12px;
            background: #333;
            color: white;
            text-align: center;
            text-decoration: none;
            margin-top: 25px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            font-weight: bold;
        }

        .btn-back {
            display: block;
            width: 100%;
            padding: 12px;
            background: #10b981;
            color: white;
            text-align: center;
            text-decoration: none;
            margin-top: 10px;
            border-radius: 6px;
            font-size: 1rem;
        }

        @media print {
            body { background: white; padding: 0; }
            .btn-print, .btn-back { display: none; }
            .receipt { border: none; box-shadow: none; }
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
                <p style="margin: 5px 0; font-size: 0.8rem;"><?= htmlspecialchars($address) ?></p>
            <?php endif; ?>
            <?php if ($phone): ?>
                <p style="margin: 2px 0; font-size: 0.8rem;">Tel: <?= htmlspecialchars($phone) ?></p>
            <?php endif; ?>
            <h1>RECIBO DE RENTA</h1>
        </div>

        <div class="row">
            <span class="label">Recibo No:</span>
            <span><strong>#<?= str_pad($receipt['id'], 6, '0', STR_PAD_LEFT) ?></strong></span>
        </div>

        <div class="row">
            <span class="label">Fecha de Emisión:</span>
            <span><?= date('d/m/Y', strtotime($receipt['created_at'])) ?></span>
        </div>

        <div class="row">
            <span class="label">Fecha de Pago:</span>
            <span><?= date('d/m/Y', strtotime($receipt['payment_date'])) ?></span>
        </div>

        <div style="margin: 20px 0;">
            <div class="row">
                <span class="label">Inquilino:</span>
                <span><?= htmlspecialchars($receipt['tenant_name']) ?></span>
            </div>
            <?php if ($receipt['tenant_id_number']): ?>
                <div class="row">
                    <span class="label">ID/Cédula:</span>
                    <span><?= htmlspecialchars($receipt['tenant_id_number']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="total-box">
            <?= $currency ?> <?= number_format($receipt['amount'], 2) ?>
        </div>

        <div style="margin: 20px 0;">
            <p class="label" style="margin-bottom: 5px;">Por concepto de:</p>
            <p style="margin: 0; background: #fefefe; padding: 10px; border: 1px solid #eee; border-radius: 4px;">
                <?= nl2br(htmlspecialchars($receipt['concept'])) ?>
            </p>
        </div>

        <div class="signature">
            <div class="sig-line">Entregué conforme</div>
            <div class="sig-line">Recibí conforme</div>
        </div>

        <div class="footer">
            <p><?= nl2br(htmlspecialchars($footer_msg)) ?></p>
            <p style="margin-top: 10px; font-size: 0.7rem; color: #888;">
                Este documento es un comprobante oficial de pago de alquiler.
            </p>
        </div>
    </div>

    <button onclick="window.print()" class="btn-print">🖨️ Imprimir Recibo</button>
    <a href="rent_receipts.php" class="btn-back">← Volver al Historial</a>
</body>
</html>
