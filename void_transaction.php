<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso inválido");
}

$transaction_id = $_POST['transaction_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'admin';

// Security check
if ($user_role === 'cobrador') {
    die("No tienes permisos para anular transacciones.");
}

try {
    $pdo->beginTransaction();

    // 1. Get Transaction Details
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        throw new Exception("Transacción no encontrada");
    }

    $loan_id = $transaction['loan_id'];

    // 2. Get details to reverse
    $stmt_details = $pdo->prepare("SELECT * FROM transaction_details WHERE transaction_id = ?");
    $stmt_details->execute([$transaction_id]);
    $details = $stmt_details->fetchAll();

    // 3. Reverse payments
    foreach ($details as $detail) {
        $payment_id = $detail['payment_id'];
        $amount = $detail['amount_applied'];
        $type = $detail['type']; // 'capital' or 'late_fee'

        // Get current payment state
        $stmt_p = $pdo->prepare("SELECT * FROM payments WHERE id = ? FOR UPDATE"); // Lock row
        $stmt_p->execute([$payment_id]);
        $payment = $stmt_p->fetch();

        if ($payment) {
            if ($type == 'capital') {
                $new_paid_amount = max(0, $payment['paid_amount'] - $amount);
                $is_paid = ($new_paid_amount >= $payment['amount_due']) && ($payment['paid_late_fee'] >= $payment['late_fee']);
                $new_status = $is_paid ? 'paid' : 'pending';

                $stmt_upd = $pdo->prepare("UPDATE payments SET paid_amount = ?, status = ? WHERE id = ?");
                $stmt_upd->execute([$new_paid_amount, $new_status, $payment_id]);
            } elseif ($type == 'late_fee') {
                $new_paid_late_fee = max(0, $payment['paid_late_fee'] - $amount);
                // Also remove the late fee charge itself if it was added manually during this transaction?
                // The current logic in process_payment adds to 'late_fee' column on payment.
                // But transaction_details only tracks what was PAID.
                // If we want to reverse the CHARGE of the late fee too, we would need to know if it was added in this transaction.
                // For now, we only reverse the PAYMENT of the late fee. The charge remains but becomes unpaid.

                $is_paid = ($payment['paid_amount'] >= $payment['amount_due']) && ($new_paid_late_fee >= $payment['late_fee']);
                $new_status = $is_paid ? 'paid' : 'pending';

                $stmt_upd = $pdo->prepare("UPDATE payments SET paid_late_fee = ?, status = ? WHERE id = ?");
                $stmt_upd->execute([$new_paid_late_fee, $new_status, $payment_id]);
            }
        }
    }

    // 4. Delete Transaction (Cascade will delete details)
    $stmt_del = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt_del->execute([$transaction_id]);

    // 5. Update Loan Status
    // Since we voided a payment, it's possible the loan is active again if it was paid
    $pdo->exec("UPDATE loans SET status = 'active' WHERE id = $loan_id");

    $pdo->commit();

    header("Location: loan_details.php?id=$loan_id&void_success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al anular transacción: " . $e->getMessage());
}
?>