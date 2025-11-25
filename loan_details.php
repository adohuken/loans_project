<?php
require 'auth.php';
require 'db.php';

$loan_id = $_GET['id'] ?? null;

if (!$loan_id) {
    header("Location: index.php");
    exit;
}

// Fetch Loan Details
$stmt = $pdo->prepare("
    SELECT l.*, c.name as client_name, c.cedula, c.phone
    FROM loans l
    JOIN clients c ON l.client_id = c.id
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><link rel='stylesheet' href='style.css'></head><body>";
    echo "<div class='container' style='text-align: center; margin-top: 50px;'>";
    echo "<div class='card' style='max-width: 500px; margin: 0 auto;'>";
    echo "<h2>Préstamo no encontrado</h2>";
    echo "<p>El préstamo que buscas no existe.</p>";
    echo "<a href='index.php' class='btn'>Volver al Inicio</a>";
    echo "</div></div></body></html>";
    exit;
}

// Fetch Payments
$stmt_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY due_date ASC");
$stmt_payments->execute([$loan_id]);
$payments = $stmt_payments->fetchAll();

// Calculate Progress
$total_paid = 0;
$total_late_fees = 0;
foreach ($payments as $p) {
    $total_paid += $p['paid_amount'];
    $total_late_fees += $p['late_fee'] ?? 0;
}
$progress = ($total_paid / $loan['total_amount']) * 100;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Préstamo</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header class="no-print">
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Préstamo #<?= $loan['id'] ?> - <?= htmlspecialchars($loan['client_name']) ?>
                    (<?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?>)</h2>
                <span class="badge badge-<?= $loan['status'] == 'active' ? 'pending' : 'paid' ?>">
                    <?= strtoupper($loan['status']) ?>
                </span>
            </div>
            <div class="grid" style="margin-top: 1rem;">
                <div>
                    <p><strong>Monto Prestado:</strong> $<?= number_format($loan['amount'], 2) ?></p>
                    <p><strong>Interés:</strong> <?= $loan['interest_rate'] ?>% Mensual</p>
                    <p><strong>Total a Pagar:</strong> $<?= number_format($loan['total_amount'], 2) ?></p>
                </div>
                <div>
                    <p><strong>Frecuencia:</strong> <?= ucfirst($loan['frequency']) ?></p>
                    <p><strong>Duración:</strong> <?= $loan['duration_months'] ?> Meses</p>
                    <p><strong>Pagado:</strong> $<?= number_format($total_paid, 2) ?></p>
                    <?php if ($total_late_fees > 0): ?>
                        <p><strong>Moras Cobradas:</strong> <span style="color: #dc2626;">$<?= number_format($total_late_fees, 2) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 1rem; background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="width: <?= $progress ?>%; background: var(--success); height: 100%;"></div>
            </div>
        </div>

        <div class="card">
            <h3>Calendario de Pagos</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha Vencimiento</th>
                        <th>Monto Cuota</th>
                        <th>Mora</th>
                        <th>Estado</th>
                        <th>Fecha Pago</th>
                        <th class="no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $index => $payment): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= $payment['due_date'] ?></td>
                            <td>$<?= number_format($payment['amount_due'], 2) ?></td>
                            <td>
                                <?php if (isset($payment['late_fee']) && $payment['late_fee'] > 0): ?>
                                    <span style="color: #dc2626; font-weight: bold;">$<?= number_format($payment['late_fee'], 2) ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $payment['status'] ?>">
                                    <?= $payment['status'] == 'paid' ? 'PAGADO' : 'PENDIENTE' ?>
                                </span>
                            </td>
                            <td><?= $payment['paid_date'] ?? '-' ?></td>
                            <td class="no-print">
                                <?php if ($payment['status'] == 'pending'): ?>
                                    <a href="process_payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm">Pagar</a>
                                <?php else: ?>
                                    <a href="receipt.php?payment_id=<?= $payment['id'] ?>" target="_blank"
                                        class="btn btn-sm btn-secondary">Ver Recibo</a>
                                <?php endif; ?>
                                <a href="edit_payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-secondary"
                                    style="margin-left: 5px;">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>