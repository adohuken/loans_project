<?php
require 'auth.php';
require 'db.php';

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $amount_due = $_POST['amount_due'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $paid_amount = $_POST['paid_amount'];
    $paid_date = !empty($_POST['paid_date']) ? $_POST['paid_date'] : null;

    // If status is pending, clear paid info
    if ($status === 'pending') {
        $paid_amount = 0;
        $paid_date = null;
    }

    $stmt = $pdo->prepare("UPDATE payments SET amount_due = ?, due_date = ?, status = ?, paid_amount = ?, paid_date = ? WHERE id = ?");
    $stmt->execute([$amount_due, $due_date, $status, $paid_amount, $paid_date, $id]);

    // Redirect back to loan details
    $stmt = $pdo->prepare("SELECT loan_id FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();

    header("Location: loan_details.php?id=" . $payment['loan_id']);
    exit;
}

// Fetch payment details
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment)
    die("Pago no encontrado");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pago - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
</head>

<body>
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <h2>Editar Pago #<?= $payment['id'] ?></h2>
            <form action="edit_payment.php" method="POST">
                <input type="hidden" name="id" value="<?= $payment['id'] ?>">

                <div class="form-group">
                    <label>Fecha de Vencimiento</label>
                    <input type="date" name="due_date" value="<?= $payment['due_date'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Monto Cuota (Esperado)</label>
                    <input type="number" name="amount_due" step="0.01" value="<?= $payment['amount_due'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="status" id="status" onchange="togglePaidFields()" required>
                        <option value="pending" <?= $payment['status'] == 'pending' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="paid" <?= $payment['status'] == 'paid' ? 'selected' : '' ?>>Pagado</option>
                    </select>
                </div>

                <div id="paidFields" style="<?= $payment['status'] == 'pending' ? 'display:none;' : '' ?>">
                    <div class="form-group">
                        <label>Monto Pagado</label>
                        <input type="number" name="paid_amount" step="0.01" value="<?= $payment['paid_amount'] ?>">
                    </div>

                    <div class="form-group">
                        <label>Fecha de Pago</label>
                        <input type="datetime-local" name="paid_date"
                            value="<?= $payment['paid_date'] ? date('Y-m-d\TH:i', strtotime($payment['paid_date'])) : '' ?>">
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Guardar Cambios</button>
                <a href="javascript:history.back()" class="btn btn-secondary"
                    style="display: block; text-align: center; margin-top: 10px;">Cancelar</a>
            </form>
        </div>
    </div>

    <script>
        function togglePaidFields() {
            const status = document.getElementById('status').value;
            const fields = document.getElementById('paidFields');
            if (status === 'paid') {
                fields.style.display = 'block';
            } else {
                fields.style.display = 'none';
            }
        }
    </script>
</body>

</html>
