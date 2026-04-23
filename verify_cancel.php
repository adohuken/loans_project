<?php
require 'db.php';
// Create a dummy loan to cancel
try {
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO clients (name, cedula) VALUES ('Test Client', '123456')");
    $client_id = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO loans (client_id, amount, total_amount, status) VALUES ($client_id, 1000, 1100, 'active')");
    $loan_id = $pdo->lastInsertId();

    echo "Created Loan ID: $loan_id with status 'active'.\n";

    // Simulate cancellation
    $pdo->exec("UPDATE loans SET status = 'cancelled' WHERE id = $loan_id");

    $stmt = $pdo->query("SELECT status FROM loans WHERE id = $loan_id");
    $status = $stmt->fetchColumn();

    echo "Loan ID: $loan_id status is now: '$status'.\n";

    if ($status === 'cancelled') {
        echo "VERIFICATION PASSED: Loan successfully cancelled.\n";
    } else {
        echo "VERIFICATION FAILED: Loan status incorrect.\n";
    }

    // Clean up
    $pdo->exec("DELETE FROM loans WHERE id = $loan_id");
    $pdo->exec("DELETE FROM clients WHERE id = $client_id");
    $pdo->commit();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    $pdo->rollBack();
}
?>