<?php
require 'db.php';
// Create dummy data
try {
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO clients (name, cedula) VALUES ('Test Edit', '88888')");
    $client_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO loans (client_id, amount, total_amount, status) VALUES ($client_id, 100, 110, 'active')");
    $loan_id = $pdo->lastInsertId();

    // Create 3 payments
    $pdo->exec("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES ($loan_id, '2025-01-01', 50, 'pending')");
    $p1 = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES ($loan_id, '2025-02-01', 50, 'pending')");
    $p2 = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES ($loan_id, '2025-03-01', 50, 'pending')");
    $p3 = $pdo->lastInsertId(); // Last one

    echo "Created payments: $p1, $p2, $p3 (Last).\n";

    // Test Check: Simulate the logic in edit_payment.php
    $stmt_all = $pdo->prepare("SELECT id FROM payments WHERE loan_id = ? ORDER BY due_date ASC");
    $stmt_all->execute([$loan_id]);
    $all_payments = $stmt_all->fetchAll(PDO::FETCH_COLUMN);
    $last_payment_id = end($all_payments);

    echo "Logic identified Last Payment ID as: $last_payment_id.\n";

    if ($last_payment_id == $p3) {
        echo "VERIFICATION PASSED: Logic correctly identifies the last payment.\n";
    } else {
        echo "VERIFICATION FAILED: Logic identified wrong payment.\n";
    }

    if ($last_payment_id != $p2) {
        echo "VERIFICATION PASSED: Logic correctly identifies checks that p2 is NOT the last payment.\n";
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