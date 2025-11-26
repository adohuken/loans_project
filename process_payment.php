<?php
require 'db.php';

$payment_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['payment_id'];
    $amount_paid = $_POST['amount'] ?? 0;
    $late_fee = $_POST['late_fee'] ?? 0;
    $only_late_fee = isset($_POST['only_late_fee']) && $_POST['only_late_fee'] == '1';

    // Get current payment info
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $current_payment = $stmt->fetch();

    if ($only_late_fee) {
        // Only register late fee, don't mark as paid
        $stmt = $pdo->prepare("UPDATE payments SET late_fee = late_fee + ? WHERE id = ?");
        $stmt->execute([$late_fee, $id]);

        // Redirect back to loan details
        $loan_id = $current_payment['loan_id'];
        header("Location: loan_details.php?id=$loan_id&late_fee_added=1");
        exit;
    } else {
        // NUEVA LÓGICA: Aplicar pago primero a mora, luego a capital
        $current_late_fee = $current_payment['late_fee'];
        $current_paid_amount = $current_payment['paid_amount'];
        $total_due = $current_payment['amount_due'];

        // Total recibido ahora (abono + nueva mora si aplica)
        $total_received = $amount_paid;
        $new_late_fee = $current_late_fee + $late_fee;

        // Aplicar pago: primero a mora, luego a capital
        $payment_to_late_fee = 0;
        $payment_to_capital = 0;

        if ($current_late_fee > 0) {
            // Hay mora pendiente: aplicar primero a mora
            if ($total_received >= $current_late_fee) {
                // El pago cubre toda la mora
                $payment_to_late_fee = $current_late_fee;
                $payment_to_capital = $total_received - $current_late_fee;
                $new_late_fee = $late_fee; // Solo queda la nueva mora agregada
            } else {
                // El pago NO cubre toda la mora
                $payment_to_late_fee = $total_received;
                $payment_to_capital = 0;
                $new_late_fee = $current_late_fee - $payment_to_late_fee + $late_fee;
            }
        } else {
            // No hay mora pendiente: todo va a capital
            $payment_to_capital = $total_received;
        }

        // Calcular nuevo monto pagado al capital
        $new_paid_amount = $current_paid_amount + $payment_to_capital;

        // Determine if payment is complete
        if ($new_paid_amount >= $total_due && $new_late_fee == 0) {
            // Full payment (capital completo Y sin mora)
            $status = 'paid';
            $paid_date = date('Y-m-d H:i:s');
        } else {
            // Partial payment - keep as pending
            $status = 'pending';
            $paid_date = $current_payment['paid_date']; // Keep existing paid_date or null
        }

        // Update payment including paid_late_fee
        if ($status == 'paid') {
            $stmt = $pdo->prepare("UPDATE payments SET status = ?, paid_amount = ?, late_fee = ?, paid_late_fee = paid_late_fee + ?, paid_date = ? WHERE id = ?");
            $stmt->execute([$status, $new_paid_amount, $new_late_fee, $payment_to_late_fee, $paid_date, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE payments SET paid_amount = ?, late_fee = ?, paid_late_fee = paid_late_fee + ? WHERE id = ?");
            $stmt->execute([$new_paid_amount, $new_late_fee, $payment_to_late_fee, $id]);
        }

        // Check if all payments are done to update loan status
        $loan_id = $current_payment['loan_id'];
        $pending = $pdo->query("SELECT COUNT(*) FROM payments WHERE loan_id = $loan_id AND status = 'pending'")->fetchColumn();

        if ($pending == 0) {
            $pdo->exec("UPDATE loans SET status = 'paid' WHERE id = $loan_id");
        }

        // Redirect based on payment status
        if ($status == 'paid') {
            header("Location: receipt.php?payment_id=$id");
        } else {
            header("Location: loan_details.php?id=$loan_id&partial_payment=1");
        }
        exit;
    }
}

// Get payment details for form
$stmt = $pdo->prepare("SELECT p.*, l.start_date FROM payments p JOIN loans l ON p.loan_id = l.id WHERE p.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

// Calculate remaining balance
$remaining_balance = $payment['amount_due'] - $payment['paid_amount'];

// Check if payment is late
$is_late = false;
$days_late = 0;
if ($payment) {
    $due_date = new DateTime($payment['due_date']);
    $today = new DateTime();
    if ($today > $due_date) {
        $is_late = true;
        $days_late = $today->diff($due_date)->days;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <style>
        .balance-indicator {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #dc2626;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.1);
        }

        .balance-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #dc2626;
            margin: 0.5rem 0;
        }

        .progress-bar-container {
            background: #e5e7eb;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <h2>Registrar Pago</h2>

            <?php if ($payment['paid_amount'] > 0): ?>
                <div class="balance-indicator">
                    <p style="margin: 0; color: #991b1b; font-weight: 600; font-size: 0.9rem;">⚠️ SALDO PENDIENTE</p>
                    <div class="balance-amount">$<?= number_format($remaining_balance, 2) ?></div>
                    <p style="margin: 0.25rem 0 0 0; color: #991b1b; font-size: 0.85rem;">
                        Ya pagado: $<?= number_format($payment['paid_amount'], 2) ?> de
                        $<?= number_format($payment['amount_due'], 2) ?>
                    </p>
                    <div class="progress-bar-container">
                        <div class="progress-bar"
                            style="width: <?= ($payment['paid_amount'] / $payment['amount_due']) * 100 ?>%">
                            <?= number_format(($payment['paid_amount'] / $payment['amount_due']) * 100, 1) ?>%
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="margin: 0.25rem 0;"><strong>Fecha de Vencimiento:</strong> <?= $payment['due_date'] ?></p>
                <p style="margin: 0.25rem 0;"><strong>Monto de la Cuota:</strong>
                    $<?= number_format($payment['amount_due'], 2) ?></p>
                <?php if ($payment['paid_amount'] > 0): ?>
                    <p style="margin: 0.25rem 0;"><strong>Abonado Anteriormente:</strong> <span
                            style="color: #10b981;">$<?= number_format($payment['paid_amount'], 2) ?></span></p>
                    <p style="margin: 0.25rem 0;"><strong>Falta por Pagar:</strong> <span
                            style="color: #dc2626; font-weight: bold;">$<?= number_format($remaining_balance, 2) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($payment['late_fee'] > 0): ?>
                    <p style="margin: 0.25rem 0;"><strong>Mora Acumulada:</strong> <span
                            style="color: #dc2626;">$<?= number_format($payment['late_fee'], 2) ?></span></p>
                <?php endif; ?>

                <?php if ($is_late): ?>
                    <div
                        style="background: #fef9c3; border: 1px solid #fde68a; padding: 0.75rem; border-radius: 6px; margin-top: 0.75rem;">
                        <p style="margin: 0; color: #854d0e; font-weight: bold;">⚠️ Pago Atrasado</p>
                        <p style="margin: 0.25rem 0 0 0; color: #854d0e; font-size: 0.9rem;">
                            Este pago tiene <?= $days_late ?> día<?= $days_late != 1 ? 's' : '' ?> de retraso
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form for Payment -->
            <form action="process_payment.php" method="POST" id="paymentForm">
                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                <input type="hidden" name="only_late_fee" id="onlyLateFee" value="0">

                <div class="form-group">
                    <label>Monto a Abonar ($)</label>
                    <input type="number" name="amount" id="amountInput" step="0.01"
                        value="<?= number_format($remaining_balance, 2, '.', '') ?>" min="0" required>
                    <small style="color: #64748b;">
                        <?php if ($payment['paid_amount'] > 0): ?>
                            Ingresa el monto que el cliente abona ahora. Falta: $<?= number_format($remaining_balance, 2) ?>
                        <?php else: ?>
                            Puedes ajustar el monto si es un pago parcial
                        <?php endif; ?>
                    </small>
                </div>

                <div class="form-group">
                    <label>Mora / Recargo ($)</label>
                    <input type="number" name="late_fee" id="lateFeeInput" step="0.01" value="0" min="0"
                        placeholder="0.00">
                    <small style="color: #64748b;">
                        <?php if ($is_late): ?>
                            Agrega un cargo por mora (<?= $days_late ?> día<?= $days_late != 1 ? 's' : '' ?> de retraso)
                        <?php else: ?>
                            Opcional: Agrega un cargo adicional si aplica
                        <?php endif; ?>
                    </small>
                </div>

                <div
                    style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #075985; font-size: 0.9rem;">
                        <strong>Total a Cobrar Ahora:</strong> <span
                            id="totalAmount">$<?= number_format($remaining_balance, 2) ?></span>
                    </p>
                    <p style="margin: 0.5rem 0 0 0; color: #075985; font-size: 0.85rem;" id="statusMessage">
                        <?php if ($remaining_balance > 0): ?>
                            Quedará pendiente: $<span id="willRemain"><?= number_format($remaining_balance, 2) ?></span>
                        <?php endif; ?>
                    </p>
                </div>

                <button type="submit" class="btn" style="width: 100%;"
                    onclick="document.getElementById('onlyLateFee').value='0'">
                    ✓ Registrar Abono
                </button>

                <?php if ($is_late): ?>
                    <button type="submit" class="btn" style="width: 100%; margin-top: 0.5rem; background: #f59e0b;"
                        onclick="document.getElementById('onlyLateFee').value='1'; return confirmLateFeeOnly();">
                        💰 Solo Registrar Mora (Sin Abonar)
                    </button>
                <?php endif; ?>

                <a href="javascript:history.back()" class="btn btn-secondary"
                    style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancelar</a>
            </form>
        </div>
    </div>

    <script>
        const amountInput = document.getElementById('amountInput');
        const lateFeeInput = document.getElementById('lateFeeInput');
        const totalSpan = document.getElementById('totalAmount');
        const statusMessage = document.getElementById('statusMessage');
        const willRemainSpan = document.getElementById('willRemain');

        const remainingBalance = <?= $remaining_balance ?>;
        const amountDue = <?= $payment['amount_due'] ?>;
        const alreadyPaid = <?= $payment['paid_amount'] ?>;

        function updateTotal() {
            const amount = parseFloat(amountInput.value) || 0;
            const lateFee = parseFloat(lateFeeInput.value) || 0;
            const total = amount + lateFee;
            totalSpan.textContent = '$' + total.toFixed(2);

            // Calculate what will remain
            const newPaid = alreadyPaid + amount;
            const willRemain = Math.max(0, amountDue - newPaid);

            if (willRemain > 0) {
                statusMessage.innerHTML = '<strong style="color: #dc2626;">⚠️ Pago Parcial:</strong> Quedará pendiente: $<span id="willRemain">' + willRemain.toFixed(2) + '</span>';
                statusMessage.style.color = '#dc2626';
            } else if (newPaid >= amountDue) {
                statusMessage.innerHTML = '<strong style="color: #10b981;">✓ Pago Completo:</strong> Esta cuota quedará saldada';
                statusMessage.style.color = '#10b981';
            }
        }

        function confirmLateFeeOnly() {
            const lateFee = parseFloat(lateFeeInput.value) || 0;
            if (lateFee <= 0) {
                alert('Debes ingresar un monto de mora mayor a 0');
                return false;
            }
            return confirm('¿Confirmas registrar solo la mora de $' + lateFee.toFixed(2) + ' sin abonar a la cuota?');
        }

        amountInput.addEventListener('input', updateTotal);
        lateFeeInput.addEventListener('input', updateTotal);

        // Initial update
        updateTotal();
    </script>
</body>

</html>