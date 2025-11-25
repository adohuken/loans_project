<?php
require 'db.php';

$payment_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['payment_id'];
    $amount_paid = $_POST['amount'] ?? 0;
    $late_fee = $_POST['late_fee'] ?? 0;
    $only_late_fee = isset($_POST['only_late_fee']) && $_POST['only_late_fee'] == '1';
    
    if ($only_late_fee) {
        // Only register late fee, don't mark as paid
        $stmt = $pdo->prepare("UPDATE payments SET late_fee = late_fee + ? WHERE id = ?");
        $stmt->execute([$late_fee, $id]);
        
        // Redirect back to loan details
        $loan_id = $pdo->query("SELECT loan_id FROM payments WHERE id = $id")->fetchColumn();
        header("Location: loan_details.php?id=$loan_id&late_fee_added=1");
        exit;
    } else {
        // Register full payment with optional late fee
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', paid_amount = ?, late_fee = late_fee + ?, paid_date = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$amount_paid, $late_fee, $id]);

        // Check if all payments are done to update loan status
        $loan_id = $pdo->query("SELECT loan_id FROM payments WHERE id = $id")->fetchColumn();
        $pending = $pdo->query("SELECT COUNT(*) FROM payments WHERE loan_id = $loan_id AND status = 'pending'")->fetchColumn();
        
        if ($pending == 0) {
            $pdo->exec("UPDATE loans SET status = 'paid' WHERE id = $loan_id");
        }

        // Redirect to receipt
        header("Location: receipt.php?payment_id=$id");
        exit;
    }
}

// Get payment details for form
$stmt = $pdo->prepare("SELECT p.*, l.start_date FROM payments p JOIN loans l ON p.loan_id = l.id WHERE p.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

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
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <h2>Registrar Pago</h2>
            
            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="margin: 0.25rem 0;"><strong>Fecha de Vencimiento:</strong> <?= $payment['due_date'] ?></p>
                <p style="margin: 0.25rem 0;"><strong>Monto de la Cuota:</strong> $<?= number_format($payment['amount_due'], 2) ?></p>
                <?php if ($payment['late_fee'] > 0): ?>
                    <p style="margin: 0.25rem 0;"><strong>Mora Acumulada:</strong> <span style="color: #dc2626;">$<?= number_format($payment['late_fee'], 2) ?></span></p>
                <?php endif; ?>
                
                <?php if ($is_late): ?>
                    <div style="background: #fef9c3; border: 1px solid #fde68a; padding: 0.75rem; border-radius: 6px; margin-top: 0.75rem;">
                        <p style="margin: 0; color: #854d0e; font-weight: bold;">⚠️ Pago Atrasado</p>
                        <p style="margin: 0.25rem 0 0 0; color: #854d0e; font-size: 0.9rem;">
                            Este pago tiene <?= $days_late ?> día<?= $days_late != 1 ? 's' : '' ?> de retraso
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Form for Full Payment -->
            <form action="process_payment.php" method="POST" id="paymentForm">
                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                <input type="hidden" name="only_late_fee" id="onlyLateFee" value="0">
                
                <div class="form-group">
                    <label>Monto a Pagar ($)</label>
                    <input type="number" name="amount" id="amountInput" step="0.01" value="<?= $payment['amount_due'] ?>" min="0">
                    <small style="color: #64748b;">Puedes ajustar el monto si es un pago parcial, o dejarlo en 0 para solo registrar mora</small>
                </div>
                
                <div class="form-group">
                    <label>Mora / Recargo ($)</label>
                    <input type="number" name="late_fee" id="lateFeeInput" step="0.01" value="0" min="0" placeholder="0.00">
                    <small style="color: #64748b;">
                        <?php if ($is_late): ?>
                            Agrega un cargo por mora (<?= $days_late ?> día<?= $days_late != 1 ? 's' : '' ?> de retraso)
                        <?php else: ?>
                            Opcional: Agrega un cargo adicional si aplica
                        <?php endif; ?>
                    </small>
                </div>
                
                <div style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #075985; font-size: 0.9rem;">
                        <strong>Total a Cobrar:</strong> <span id="totalAmount">$<?= number_format($payment['amount_due'], 2) ?></span>
                    </p>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;" onclick="document.getElementById('onlyLateFee').value='0'">
                    ✓ Registrar Pago Completo
                </button>
                
                <button type="submit" class="btn" style="width: 100%; margin-top: 0.5rem; background: #f59e0b;" 
                        onclick="document.getElementById('onlyLateFee').value='1'; return confirmLateFeeOnly();">
                    💰 Solo Registrar Mora (Sin Abonar)
                </button>
                
                <a href="javascript:history.back()" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancelar</a>
            </form>
        </div>
    </div>
    
    <script>
        // Calculate total when amount or late fee changes
        const amountInput = document.getElementById('amountInput');
        const lateFeeInput = document.getElementById('lateFeeInput');
        const totalSpan = document.getElementById('totalAmount');
        
        function updateTotal() {
            const amount = parseFloat(amountInput.value) || 0;
            const lateFee = parseFloat(lateFeeInput.value) || 0;
            const total = amount + lateFee;
            totalSpan.textContent = '$' + total.toFixed(2);
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
    </script>
</body>

</html>