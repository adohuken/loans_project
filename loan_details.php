<?php
require 'auth.php';
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: active_loans.php");
    exit;
}

$loan_id = $_GET['id'];
$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;

// Fetch Loan Details
$stmt = $pdo->prepare("
    SELECT l.*, c.name, c.cedula, c.address, c.phone, p.name as portfolio_name, c.portfolio_id
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    LEFT JOIN portfolios p ON c.portfolio_id = p.id
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Préstamo no encontrado.");
}

// Security Check for Cobrador
if ($user_role === 'cobrador') {
    if ($loan['portfolio_id'] != $user_portfolio_id) {
        die("Acceso denegado: Este préstamo no pertenece a tu cartera asignada.");
    }
}

// Fetch Payments
$stmt_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY due_date ASC");
$stmt_payments->execute([$loan_id]);
$payments = $stmt_payments->fetchAll();

// Calculate Progress
$total_paid = 0;
foreach ($payments as $p) {
    $total_paid += $p['paid_amount'];
}
$progress = ($loan['total_amount'] > 0) ? ($total_paid / $loan['total_amount']) * 100 : 0;

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Préstamo #<?= $loan['id'] ?></title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header class="no-print">
            <h1><i class="fas fa-file-invoice-dollar"></i> Sistema de Préstamos</h1>
            <nav>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                    <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <?php endif; ?>
                <a href="active_loans.php" class="active"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                    <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                    <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                        <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                    <?php endif; ?>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </nav>
        </header>

        <div class="grid grid-2-3">
            <!-- Loan Info -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h2><i class="fas fa-info-circle"></i> Información del Préstamo</h2>
                    <button onclick="window.print()" class="btn btn-sm btn-secondary no-print"><i
                            class="fas fa-print"></i> Imprimir</button>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-solid); margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($loan['name']) ?>
                    </h3>
                    <p><i class="fas fa-id-card"></i> <?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?></p>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($loan['phone'] ?? 'N/A') ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($loan['address'] ?? 'N/A') ?></p>
                    <?php if ($loan['portfolio_name']): ?>
                        <p style="margin-top: 0.5rem;">
                            <span class="badge" style="background-color: #e0e7ff; color: #4338ca;">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($loan['portfolio_name']) ?>
                            </span>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <small style="color: #64748b;">Monto Prestado</small>
                        <p style="font-weight: bold; font-size: 1.1rem;">
                            <?= $currency ?><?= number_format($loan['amount'], 2) ?>
                        </p>
                    </div>
                    <div>
                        <small style="color: #64748b;">Total a Pagar</small>
                        <p style="font-weight: bold; font-size: 1.1rem; color: var(--primary-solid);">
                            <?= $currency ?><?= number_format($loan['total_amount'], 2) ?>
                        </p>
                    </div>
                    <div>
                        <small style="color: #64748b;">Frecuencia</small>
                        <p style="font-weight: bold; text-transform: capitalize;"><?= $loan['frequency'] ?></p>
                    </div>
                    <div>
                        <small style="color: #64748b;">Estado</small>
                        <p>
                            <span class="badge badge-<?= $loan['status'] == 'active' ? 'pending' : 'paid' ?>">
                                <?= strtoupper($loan['status']) ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <small style="color: #64748b;">Progreso de Pago</small>
                    <div
                        style="background: #e2e8f0; height: 10px; border-radius: 5px; margin-top: 5px; overflow: hidden;">
                        <div style="background: var(--success); width: <?= $progress ?>%; height: 100%;"></div>
                    </div>
                    <p style="text-align: right; font-size: 0.9rem; margin-top: 5px;">
                        <?= number_format($progress, 1) ?>%
                    </p>
                </div>
            </div>

            <!-- Payment Schedule -->
            <div class="card">
                <h2><i class="fas fa-calendar-alt"></i> Calendario de Pagos</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha Vencimiento</th>
                                <th>Monto Cuota</th>
                                <th>Abonado</th>
                                <th>Saldo</th>
                                <th>Mora</th>
                                <th>Estado</th>
                                <th>Fecha Pago</th>
                                <th class="no-print">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $index => $payment):
                                $is_late = ($payment['status'] == 'pending' && strtotime($payment['due_date']) < strtotime(date('Y-m-d')));
                                $balance = $payment['amount_due'] - $payment['paid_amount'];
                                $is_partial = $payment['paid_amount'] > 0 && $payment['paid_amount'] < $payment['amount_due'];
                                ?>
                                <tr style="<?= $is_partial ? 'background-color: #fffbeb;' : '' ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td style="<?= $is_late ? 'color: #ef4444; font-weight: bold;' : '' ?>">
                                        <?= $payment['due_date'] ?>
                                        <?php if ($is_late): ?>
                                            <i class="fas fa-exclamation-circle" title="Atrasado"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $currency ?><?= number_format($payment['amount_due'], 2) ?></td>

                                    <!-- Columna Abonado -->
                                    <td
                                        style="color: <?= $payment['paid_amount'] > 0 ? '#10b981' : '#64748b' ?>; font-weight: bold;">
                                        <?= $currency ?>     <?= number_format($payment['paid_amount'], 2) ?>
                                        <?php if ($is_partial): ?>
                                            <br><small
                                                style="color: #f59e0b; font-weight: normal;">(<?= number_format(($payment['paid_amount'] / $payment['amount_due']) * 100, 0) ?>%)</small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Columna Saldo -->
                                    <td>
                                        <?php if ($payment['status'] == 'paid'): ?>
                                            <span style="color: #10b981;">-</span>
                                        <?php else: ?>
                                            <span
                                                style="color: #ef4444; font-weight: bold;"><?= $currency ?><?= number_format($balance, 2) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td style="color: #ef4444;">
                                        <?= $payment['late_fee'] > 0 ? $currency . number_format($payment['late_fee'], 2) : '-' ?>
                                    </td>

                                    <td>
                                        <?php if ($payment['status'] == 'paid'): ?>
                                            <span class="badge badge-paid"><i class="fas fa-check"></i> PAGADO</span>
                                        <?php elseif ($is_partial): ?>
                                            <span class="badge" style="background: #f59e0b; color: white;"><i
                                                    class="fas fa-adjust"></i> PARCIAL</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">PENDIENTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($payment['paid_date']) && $payment['paid_date'] != '0000-00-00 00:00:00') {
                                            echo date('d/m/Y', strtotime($payment['paid_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="no-print">
                                        <?php if ($payment['status'] != 'paid'): ?>
                                            <a href="process_payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm">
                                                <i class="fas fa-money-bill"></i> <?= $is_partial ? 'Completar' : 'Pagar' ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="receipt.php?payment_id=<?= $payment['id'] ?>" target="_blank"
                                                class="btn btn-sm btn-secondary">
                                                <i class="fas fa-receipt"></i> Recibo
                                            </a>
                                            <?php if ($user_role !== 'cobrador'): ?>
                                                <!-- Opcional: Botón para editar pago solo para admins -->
                                                <!-- <a href="edit_payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a> -->
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>