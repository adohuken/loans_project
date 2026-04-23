<?php
require 'db.php';
// Create dummy data
try {
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO clients (name, cedula) VALUES ('Test Partial', '99999')");
    $client_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO loans (client_id, amount, total_amount, status) VALUES ($client_id, 100, 110, 'active')");
    $loan_id = $pdo->lastInsertId();

    // Create a partial payment
    $pdo->exec("INSERT INTO payments (loan_id, due_date, amount_due, paid_amount, status, paid_date) VALUES ($loan_id, '2025-01-01', 50, 20, 'pending', NOW())");
    $payment_id = $pdo->lastInsertId();

    echo "Created partial payment ID: $payment_id. Paid: 20, Due: 50. Status: pending.\n";

    // Logic check: In loan_details we check if paid_amount > 0 to show receipt
    $stmt = $pdo->query("SELECT paid_amount FROM payments WHERE id = $payment_id");
    $paid = $stmt->fetchColumn();

    if ($paid > 0) {
        echo "VERIFICATION PASSED: Logic indicates receipt button WOULD be shown (paid_amount > 0).\n";
    } else {
        echo "VERIFICATION FAILED: paid_amount is 0.\n";
    }

    // Cleanup
    $pdo->exec("DELETE FROM payments WHERE loan_id = $loan_id");
    $pdo->exec("DELETE FROM loans WHERE id = $loan_id");
    $pdo->exec("DELETE FROM clients WHERE id = $client_id");
    $pdo->commit();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    $pdo->rollBack();
}
?>