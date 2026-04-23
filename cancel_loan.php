<?php
require 'auth.php';
require 'db.php';

// Check if user has permission (Admin or SuperAdmin only)
$user_role = $_SESSION['role'] ?? null;
if (!in_array($user_role, ['admin', 'superadmin'])) {
    die("Acceso denegado. Solo administradores pueden cancelar préstamos.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = $_POST['loan_id'] ?? null;

    if (!$loan_id) {
        die("ID de préstamo no proporcionado.");
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Verify loan exists
        $stmt_check = $pdo->prepare("SELECT status FROM loans WHERE id = ?");
        $stmt_check->execute([$loan_id]);
        $loan = $stmt_check->fetch();

        if (!$loan) {
            throw new Exception("Préstamo no encontrado.");
        }

        if ($loan['status'] === 'cancelled') {
            throw new Exception("El préstamo ya está cancelado.");
        }

        // Determine target status
        // HARD DELETE requested by user: "al cancelar el credito tiene q borrar todo"

        $stmt_delete = $pdo->prepare("DELETE FROM loans WHERE id = ?");
        $stmt_delete->execute([$loan_id]);

        $pdo->commit();

        // Redirect to active loans list since the details page no longer exists
        header("Location: active_loans.php?msg=deleted");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al cancelar el préstamo: " . $e->getMessage());
    }
} else {
    // If accessed directly without POST, redirect home
    header("Location: index.php");
    exit;
}
?>