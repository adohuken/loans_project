<?php
require 'auth.php';
require 'db.php';

$loan_id = $_GET['loan_id'] ?? $_POST['loan_id'] ?? 0;

if (!$loan_id) {
    header("Location: active_loans.php");
    exit;
}

// Fetch Loan Details for context
$stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Préstamo no encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_due = $_POST['amount_due'];
    $due_date = $_POST['due_date'];

    if ($amount_due > 0 && !empty($due_date)) {
        // 1. Insert Payment
        $stmt_insert = $pdo->prepare("INSERT INTO payments (loan_id, amount_due, due_date, status) VALUES (?, ?, ?, 'pending')");
        $stmt_insert->execute([$loan_id, $amount_due, $due_date]);

        // 2. Update Loan Total
        $stmt_update = $pdo->prepare("UPDATE loans SET total_amount = total_amount + ? WHERE id = ?");
        $stmt_update->execute([$amount_due, $loan_id]);

        header("Location: loan_details.php?id=" . $loan_id . "&msg=added");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Cuota - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="mobile.css?v=1.0">
</head>

<body>
    <div class="container">
        <div class="card" style="max-width: 500px; margin: 2rem auto;">
            <h2><i class="fas fa-plus-circle"></i> Agregar Cuota Manual</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">
                Agrega una cuota al préstamo de <strong>
                    <?= htmlspecialchars($loan['client_id']) // Ideally Name but keeping it simple ?>
                </strong>.
                Esto aumentará el saldo total del préstamo.
            </p>

            <form method="POST">
                <input type="hidden" name="loan_id" value="<?= $loan_id ?>">

                <div class="form-group">
                    <label>Fecha de Vencimiento</label>
                    <input type="date" name="due_date" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label>Monto de la Cuota</label>
                    <input type="number" step="0.01" name="amount_due" required placeholder="0.00">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Agregar Cuota</button>
            </form>

            <div style="margin-top: 1rem; text-align: center;">
                <a href="loan_details.php?id=<?= $loan_id ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</body>

</html>