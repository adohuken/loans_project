<?php
require 'auth.php';
require 'db.php';

// Check user role and filter loans accordingly
$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;

if ($user_role === 'cobrador') {
    // Cobrador: Only see loans from their assigned portfolio
    if (!$user_portfolio_id) {
        die("Error: No tienes una cartera asignada. Contacta al administrador.");
    }

    $stmt = $pdo->prepare("
        SELECT l.*, c.name, c.cedula, p.name as portfolio_name
        FROM loans l 
        JOIN clients c ON l.client_id = c.id 
        LEFT JOIN portfolios p ON c.portfolio_id = p.id
        WHERE l.status = 'active' AND c.portfolio_id = ?
        ORDER BY l.id DESC
    ");
    $stmt->execute([$user_portfolio_id]);
    $loans = $stmt->fetchAll();

    // Get portfolio name
    $stmt_portfolio = $pdo->prepare("SELECT name FROM portfolios WHERE id = ?");
    $stmt_portfolio->execute([$user_portfolio_id]);
    $portfolio_name = $stmt_portfolio->fetchColumn();
} else {
    // Admin/SuperAdmin: See all loans
    $stmt = $pdo->query("
        SELECT l.*, c.name, c.cedula, p.name as portfolio_name
        FROM loans l 
        JOIN clients c ON l.client_id = c.id 
        LEFT JOIN portfolios p ON c.portfolio_id = p.id
        WHERE l.status = 'active'
        ORDER BY l.id DESC
    ");
    $loans = $stmt->fetchAll();
    $portfolio_name = null;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonar (Créditos Activos) - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-hand-holding-usd"></i> Sistema de Préstamos</h1>
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

        <div class="card">
            <h2><i class="fas fa-list-ul"></i> Créditos Activos (Abonar)</h2>
            <?php if ($portfolio_name): ?>
                <div
                    style="background: #e0e7ff; border: 1px solid #c7d2fe; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong style="color: #4338ca;"><i class="fas fa-folder-open"></i> Cartera:
                        <?= htmlspecialchars($portfolio_name) ?></strong>
                </div>
            <?php endif; ?>
            <p style="color: #64748b; margin-bottom: 1rem;">Selecciona un préstamo para registrar un pago.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <?php if ($user_role !== 'cobrador'): ?>
                                <th>Cartera</th>
                            <?php endif; ?>
                            <th>Monto Total</th>
                            <th>Fecha Inicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>#<?= $loan['id'] ?></td>
                                <td>
                                    <strong><i class="fas fa-user"></i> <?= htmlspecialchars($loan['name']) ?></strong><br>
                                    <small><?= htmlspecialchars($loan['cedula'] ?? '') ?></small>
                                </td>
                                <?php if ($user_role !== 'cobrador'): ?>
                                    <td>
                                        <?php if ($loan['portfolio_name']): ?>
                                            <span class="badge" style="background-color: #e0e7ff; color: #4338ca;">
                                                <i class="fas fa-folder"></i> <?= htmlspecialchars($loan['portfolio_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Sin Asignar</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>$<?= number_format($loan['total_amount'], 2) ?></td>
                                <td><?= $loan['start_date'] ?></td>
                                <td>
                                    <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-sm">
                                        <i class="fas fa-money-bill-wave"></i> Abonar / Ver Detalles
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($loans)): ?>
                            <tr>
                                <td colspan="<?= $user_role !== 'cobrador' ? '6' : '5' ?>"
                                    style="text-align: center; padding: 2rem;">
                                    <?php if ($portfolio_name): ?>
                                        No hay créditos activos en la cartera "<?= htmlspecialchars($portfolio_name) ?>".
                                    <?php else: ?>
                                        No hay créditos activos en este momento.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>