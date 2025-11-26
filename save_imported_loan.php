<?php
require 'auth.php';
require 'db.php';

try {
    // Get form data
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $duration_months = $_POST['duration_months'];
    $frequency = $_POST['frequency'];
    $start_date = $_POST['start_date'];
    $payments_made = (int) $_POST['payments_made'];
    $total_paid = $_POST['total_paid'];

    // Calculate total amount (same logic as create_loan)
    $total_interest = $amount * ($interest_rate / 100) * $duration_months;
    $total_amount = $amount + $total_interest;

    // Calculate number of payments
    $num_payments = 0;
    if ($frequency === 'weekly')
        $num_payments = $duration_months * 4;
    if ($frequency === 'biweekly')
        $num_payments = $duration_months * 2;
    if ($frequency === 'monthly')
        $num_payments = $duration_months * 1;

    $installment_amount = $total_amount / $num_payments;

    // Start transaction
    $pdo->beginTransaction();

    // Insert loan
    $stmt = $pdo->prepare("
        INSERT INTO loans (client_id, amount, interest_rate, duration_months, frequency, start_date, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$client_id, $amount, $interest_rate, $duration_months, $frequency, $start_date, $total_amount]);
    $loan_id = $pdo->lastInsertId();

    // Generate payment schedule
    $current_date = new DateTime($start_date);

    for ($i = 1; $i <= $num_payments; $i++) {
        // Calculate due date
        if ($frequency === 'weekly') {
            $due_date = clone $current_date;
            $due_date->modify('+' . (($i - 1) * 7) . ' days');
        } elseif ($frequency === 'biweekly') {
            $due_date = clone $current_date;
            $due_date->modify('+' . (($i - 1) * 14) . ' days');
        } else { // monthly
            $due_date = clone $current_date;
            $due_date->modify('+' . ($i - 1) . ' months');
        }

        // Determine if this payment was already made
        $is_paid = ($i <= $payments_made);
        $status = $is_paid ? 'paid' : 'pending';

        // Calculate paid amount for this installment
        // Distribute the total_paid proportionally across paid installments
        $paid_amount = 0;
        if ($is_paid && $payments_made > 0) {
            $paid_amount = $total_paid / $payments_made;
        }

        // For paid installments, set paid_date to the due_date (approximation)
        $paid_date = $is_paid ? $due_date->format('Y-m-d') : null;

        // Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (loan_id, amount_due, paid_amount, due_date, paid_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $loan_id,
            $installment_amount,
            $paid_amount,
            $due_date->format('Y-m-d'),
            $paid_date,
            $status
        ]);
    }

    $pdo->commit();

    // Redirect to loan details
    header("Location: loan_details.php?id=$loan_id&imported=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error al importar préstamo: " . $e->getMessage());
}
?>
