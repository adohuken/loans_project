<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = $_POST['loan_id'] ?? 0;

    if (!$loan_id) {
        die("ID de préstamo no proporcionado.");
    }

    // 1. Calcular la suma real de las cuotas existentes
    $stmt_sum = $pdo->prepare("SELECT SUM(amount_due) FROM payments WHERE loan_id = ?");
    $stmt_sum->execute([$loan_id]);
    $real_total = $stmt_sum->fetchColumn() ?: 0;

    echo "Calculated Real Total: " . $real_total . "<br>"; // DEBUG

    // 2. Actualizar el total del préstamo
    $stmt_update = $pdo->prepare("UPDATE loans SET total_amount = ? WHERE id = ?");
    $result = $stmt_update->execute([$real_total, $loan_id]);

    echo "Update Result: " . ($result ? "Success" : "Failed") . "<br>"; // DEBUG
    echo "Rows affected: " . $stmt_update->rowCount() . "<br>"; // DEBUG

    echo '<br><a href="loan_details.php?id=' . $loan_id . '">Volver al Préstamo</a>';
    exit;
} else {
    die("Método no permitido.");
}
