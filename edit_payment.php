<?php
require 'auth.php';
require 'db.php';

$id = $_POST['id'] ?? $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

// VALIDATION: Check for subsequent payments
// You cannot edit/annul a payment if a later payment has already been made.
// This enforces chronological consistency.
$stmt_check_future = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE loan_id = ? AND id > ? AND paid_amount > 0");
$stmt_check_future->execute([$payment['loan_id'], $id]);
$has_future_payments = $stmt_check_future->fetchColumn();

if ($has_future_payments > 0) {
    die("
        <div style='font-family: sans-serif; padding: 2rem; text-align: center; color: #444;'>
            <h2 style='color: #ef4444;'>🚫 Acción Bloqueada</h2>
            <p>No puedes modificar ni anular esta cuota porque <b>existen pagos posteriores registrados</b>.</p>
            <p>Por lógica contable, debes anular las cuotas más recientes primero.</p>
            <br>
            <a href='javascript:history.back()' style='padding: 10px 20px; background: #64748b; color: white; text-decoration: none; border-radius: 5px;'>Volver</a>
        </div>
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    // ACTION: DELETE (Eliminar Cuota)
    if ($action === 'delete') {
        // Obtenemos el monto de la cuota a eliminar
        $amount_to_reduce = $payment['amount_due'];

        // Validation: Only allow deleting if it's the last payment (already checked above)
        $stmt_delete = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $stmt_delete->execute([$id]);

        // Actualizamos el total del préstamo restando el monto de la cuota eliminada
        $stmt_update_loan = $pdo->prepare("UPDATE loans SET total_amount = total_amount - ? WHERE id = ?");
        $stmt_update_loan->execute([$amount_to_reduce, $payment['loan_id']]);

        header("Location: loan_details.php?id=" . $payment['loan_id']);
        exit;
    }

    // ACTION: REVERSE (Anular Pago)
    if ($action === 'reverse') {
        // Reset payment to pending, clear paid amount and date
        $stmt_reverse = $pdo->prepare("UPDATE payments SET paid_amount = 0, status = 'pending', paid_date = NULL WHERE id = ?");
        $stmt_reverse->execute([$id]);

        header("Location: loan_details.php?id=" . $payment['loan_id']);
        exit;
    }

    // ACTION: UPDATE (Editar Montos)
    $amount_due = $_POST['amount_due'];
    $paid_amount = $_POST['paid_amount'] ?? 0;
    $due_date = $_POST['due_date'];

    // Calculate new status
    $new_status = 'pending';
    if ($paid_amount >= $amount_due) {
        $new_status = 'paid';
    }

    // Calculamos la diferencia del amount_due para ajustar el total del préstamo
    $old_amount_due = $payment['amount_due'];
    $difference = $amount_due - $old_amount_due;

    // Logic for paid_date: if it was 0 and now > 0, set date. If becoming 0, clear date.
    $paid_date = $payment['paid_date'];
    if ($paid_amount > 0 && ($payment['paid_amount'] == 0)) {
        $paid_date = date('Y-m-d H:i:s');
    } elseif ($paid_amount == 0) {
        $paid_date = NULL;
    }

    $stmt = $pdo->prepare("UPDATE payments SET amount_due = ?, paid_amount = ?, due_date = ?, status = ?, paid_date = ? WHERE id = ?");
    $stmt->execute([$amount_due, $paid_amount, $due_date, $new_status, $paid_date, $id]);

    // Actualizamos el total del préstamo con la diferencia del amount_due
    if ($difference != 0) {
        $stmt_update_loan = $pdo->prepare("UPDATE loans SET total_amount = total_amount + ? WHERE id = ?");
        $stmt_update_loan->execute([$difference, $payment['loan_id']]);
    }

    header("Location: loan_details.php?id=" . $payment['loan_id']);
    exit;
}

require 'components/enhanced_header.php';
?>
<div class="container">
    <div class="card" style="max-width: 600px; margin: 2rem auto;">
        <h2>Editar Pago #<?= $payment['id'] ?></h2>
        <form action="edit_payment.php" method="POST">
            <input type="hidden" name="id" value="<?= $payment['id'] ?>">
            <input type="hidden" name="status" value="<?= $payment['status'] ?>">

            <div class="form-group">
                <label>Fecha de Vencimiento</label>
                <input type="date" name="due_date" value="<?= $payment['due_date'] ?>" readonly
                    style="background-color: #f1f5f9; color: #64748b; cursor: not-allowed;">
            </div>

            <div class="form-group">
                <div class="form-group">
                    <label>Monto Cuota (Esperado)</label>
                    <input type="number" name="amount_due" step="0.01" value="<?= $payment['amount_due'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Monto Abonado (Real)</label>
                    <input type="number" name="paid_amount" step="0.01" value="<?= $payment['paid_amount'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Estado Actual</label>
                    <input type="text"
                        value="<?= $payment['status'] == 'active' ? 'Activo' : ($payment['status'] == 'paid' ? 'Pagado' : 'Pendiente') ?>"
                        readonly style="background-color: #f1f5f9; color: #64748b; cursor: not-allowed;">
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-bottom: 1rem;">Guardar Cambios</button>
        </form>

        <!-- Botón Eliminar Pago (Anular) - Solo si hay algo pagado -->
        <?php if ($payment['paid_amount'] > 0): ?>
            <form action="edit_payment.php" method="POST" id="reversePaymentForm">
                <input type="hidden" name="id" value="<?= $payment['id'] ?>">
                <input type="hidden" name="action" value="reverse">
                <button type="submit" class="btn"
                    style="width: 100%; background-color: #ef4444; color: white; margin-bottom: 2rem;">
                    <i class="fas fa-undo"></i> Anular Pago
                </button>
            </form>
        <?php endif; ?>

        <a href="javascript:history.back()" class="btn btn-secondary"
            style="display: block; text-align: center;">Cancelar</a>
    </div>
</div>
<script>
    document.getElementById('reversePaymentForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent default submission

        Swal.fire({
            title: '¿Anular Pago?',
            html: `
                    <div style="text-align: left; font-size: 0.95rem; color: #475569;">
                        <p style="margin-bottom: 0.5rem;">Estás a punto de anular este abono.</p>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="display: flex; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <i class="fas fa-undo" style="color: #ef4444; margin-top: 3px;"></i>
                                Se reversará el abono de <strong>$<?= number_format($payment['paid_amount'], 2) ?></strong>
                            </li>
                            <li style="display: flex; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <i class="fas fa-file-invoice-dollar" style="color: #f59e0b; margin-top: 3px;"></i>
                                La deuda volverá al saldo del préstamo
                            </li>
                            <li style="display: flex; gap: 0.5rem;">
                                <i class="fas fa-clock" style="color: #3b82f6; margin-top: 3px;"></i>
                                El estado cambiará a <strong>PENDIENTE</strong>
                            </li>
                        </ul>
                    </div>
                `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Anular Pago',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit(); // Submit the form if confirmed
            }
        });
    });
</script>
</body>

</html>