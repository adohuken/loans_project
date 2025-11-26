<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$portfolio_filter = $_GET['portfolio'] ?? 'all';

// Get all portfolios for the filter dropdown
$portfolios = $pdo->query("SELECT id, name FROM portfolios ORDER BY name")->fetchAll();

// 1. Total Lent in Range
if ($portfolio_filter === 'all') {
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM loans WHERE start_date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
} else {
    $stmt = $pdo->prepare("
        SELECT SUM(l.amount) 
        FROM loans l 
        JOIN clients c ON l.client_id = c.id 
        WHERE l.start_date BETWEEN ? AND ? 
        AND c.portfolio_id = ?
    ");
    $stmt->execute([$start_date, $end_date, $portfolio_filter]);
}
$total_lent = $stmt->fetchColumn() ?: 0;

// 2. Total Collected in Range
if ($portfolio_filter === 'all') {
    $stmt = $pdo->prepare("SELECT SUM(paid_amount) FROM payments WHERE paid_date BETWEEN ? AND ? AND status = 'paid'");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
} else {
    $stmt = $pdo->prepare("
        SELECT SUM(p.paid_amount) 
        FROM payments p 
        JOIN loans l ON p.loan_id = l.id 
        JOIN clients c ON l.client_id = c.id 
        WHERE p.paid_date BETWEEN ? AND ? 
        AND p.status = 'paid' 
        AND c.portfolio_id = ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $portfolio_filter]);
}
$total_collected = $stmt->fetchColumn() ?: 0;

// 3. Outstanding Balance (Capital + Interest + Pending Late Fees)
if ($portfolio_filter === 'all') {
    $total_outstanding = $pdo->query("SELECT SUM((amount_due - paid_amount) + late_fee) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;
} else {
    $stmt = $pdo->prepare("
        SELECT SUM((p.amount_due - p.paid_amount) + p.late_fee) 
        FROM payments p 
        JOIN loans l ON p.loan_id = l.id 
        JOIN clients c ON l.client_id = c.id 
        WHERE p.status = 'pending' 
        AND c.portfolio_id = ?
    ");
    $stmt->execute([$portfolio_filter]);
    $total_outstanding = $stmt->fetchColumn() ?: 0;
}

// 4. Total Late Fees (Toda la mora registrada = Ganancia Neta)
// Suma la mora ya cobrada + la mora pendiente (todo lo que se ha registrado)
if ($portfolio_filter === 'all') {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(paid_late_fee), 0) + COALESCE(SUM(late_fee), 0) as total_late_fees
        FROM payments
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(p.paid_late_fee), 0) + COALESCE(SUM(p.late_fee), 0) as total_late_fees
        FROM payments p 
        JOIN loans l ON p.loan_id = l.id 
        JOIN clients c ON l.client_id = c.id 
        WHERE c.portfolio_id = ?
    ");
    $stmt->execute([$portfolio_filter]);
}
$total_late_fees = $stmt->fetchColumn() ?: 0;

// 5. Portfolio Statistics
if ($portfolio_filter === 'all') {
    $portfolio_stats = $pdo->query("
        SELECT 
            COALESCE(p.name, 'Sin Asignar') as portfolio_name,
            COUNT(DISTINCT c.id) as total_clients,
            COUNT(DISTINCT l.id) as total_loans,
            COALESCE(SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END), 0) as active_loans,
            COALESCE(SUM(l.amount), 0) as total_lent,
            COALESCE(SUM(l.total_amount), 0) as total_expected,
            COALESCE(SUM(pay.paid_amount), 0) as total_collected,
            COALESCE(SUM(pay.paid_late_fee) + SUM(pay.late_fee), 0) as total_late_fees_registered,
            COALESCE(SUM(CASE WHEN pay.status = 'pending' THEN pay.amount_due - pay.paid_amount ELSE 0 END), 0) as pending_balance
        FROM portfolios p
        LEFT JOIN clients c ON p.id = c.portfolio_id
        LEFT JOIN loans l ON c.id = l.client_id
        LEFT JOIN payments pay ON l.id = pay.loan_id
        GROUP BY p.id, p.name
        
        UNION ALL
        
        SELECT 
            'Sin Asignar' as portfolio_name,
            COUNT(DISTINCT c.id) as total_clients,
            COUNT(DISTINCT l.id) as total_loans,
            COALESCE(SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END), 0) as active_loans,
            COALESCE(SUM(l.amount), 0) as total_lent,
            COALESCE(SUM(l.total_amount), 0) as total_expected,
            COALESCE(SUM(pay.paid_amount), 0) as total_collected,
            COALESCE(SUM(pay.paid_late_fee) + SUM(pay.late_fee), 0) as total_late_fees_registered,
            COALESCE(SUM(CASE WHEN pay.status = 'pending' THEN pay.amount_due - pay.paid_amount ELSE 0 END), 0) as pending_balance
        FROM clients c
        LEFT JOIN loans l ON c.id = l.client_id
        LEFT JOIN payments pay ON l.id = pay.loan_id
        WHERE c.portfolio_id IS NULL
        HAVING total_clients > 0
        
        ORDER BY total_lent DESC
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(p.name, 'Sin Asignar') as portfolio_name,
            COUNT(DISTINCT c.id) as total_clients,
            COUNT(DISTINCT l.id) as total_loans,
            COALESCE(SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END), 0) as active_loans,
            COALESCE(SUM(l.amount), 0) as total_lent,
            COALESCE(SUM(l.total_amount), 0) as total_expected,
            COALESCE(SUM(pay.paid_amount), 0) as total_collected,
            COALESCE(SUM(pay.paid_late_fee) + SUM(pay.late_fee), 0) as total_late_fees_registered,
            COALESCE(SUM(CASE WHEN pay.status = 'pending' THEN pay.amount_due - pay.paid_amount ELSE 0 END), 0) as pending_balance
        FROM portfolios p
        LEFT JOIN clients c ON p.id = c.portfolio_id
        LEFT JOIN loans l ON c.id = l.client_id
        LEFT JOIN payments pay ON l.id = pay.loan_id
        WHERE p.id = ?
        GROUP BY p.id, p.name
    ");
    $stmt->execute([$portfolio_filter]);
    $portfolio_stats = $stmt->fetchAll();
}

// Fetch Settings for Currency
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Financieros - <?= htmlspecialchars($company_name) ?></title>
    <link rel="stylesheet" href="style.css?v=3.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-chart-line"></i> <?= htmlspecialchars($company_name) ?></h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php" class="active"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </header>

        <div class="card no-print">
            <h2><i class="fas fa-filter"></i> Filtrar Reporte</h2>
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label>Cartera</label>
                    <select name="portfolio">
                        <option value="all" <?= $portfolio_filter === 'all' ? 'selected' : '' ?>>Todas las Carteras</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>" <?= $portfolio_filter == $portfolio['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($portfolio['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label>Fecha Inicio</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label>Fecha Fin</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>">
                </div>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Filtrar</button>
                <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i>
                    Imprimir / PDF</button>
            </form>
        </div>

        <div class="grid" style="margin-top: 2rem;">
            <div class="card" style="border-left: 4px solid #3b82f6;">
                <h3><i class="fas fa-hand-holding-usd"></i> Total Prestado</h3>
                <small style="color: #64748b;">En el periodo seleccionado</small>
                <p style="font-size: 2rem; font-weight: bold; color: #1e293b;">
                    <?= $currency ?><?= number_format($total_lent, 2) ?>
                </p>
            </div>
            <div class="card" style="border-left: 4px solid #10b981;">
                <h3><i class="fas fa-money-bill-wave"></i> Total Recaudado</h3>
                <small style="color: #64748b;">En el periodo seleccionado (Capital + Interés)</small>
                <p style="font-size: 2rem; font-weight: bold; color: #10b981;">
                    <?= $currency ?><?= number_format($total_collected, 2) ?>
                </p>
            </div>
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3><i class="fas fa-hourglass-half"></i> Saldo Pendiente Total</h3>
                <small style="color: #64748b;">De todos los préstamos activos</small>
                <p style="font-size: 2rem; font-weight: bold; color: #f59e0b;">
                    <?= $currency ?><?= number_format($total_outstanding, 2) ?>
                </p>
            </div>
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <h3><i class="fas fa-chart-line"></i> Mora Registrada</h3>
                <small style="color: #64748b;">Ganancia Neta (Total de Moras)</small>
                <p style="font-size: 2rem; font-weight: bold; color: #8b5cf6;">
                    <?= $currency ?><?= number_format($total_late_fees, 2) ?>
                </p>
            </div>
        </div>

        <!-- Portfolio Statistics -->
        <div class="card" style="margin-top: 2rem;">
            <h2><i class="fas fa-briefcase"></i> Estadísticas por Cartera</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">Análisis detallado del rendimiento de cada cartera</p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Cartera</th>
                            <th>Clientes</th>
                            <th>Préstamos</th>
                            <th>Activos</th>
                            <th>Total Prestado</th>
                            <th>Total Esperado</th>
                            <th>Recaudado</th>
                            <th>Mora Registrada</th>
                            <th>Saldo Capital</th>
                            <th>% Recuperación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portfolio_stats as $stat):
                            $recovery_rate = $stat['total_expected'] > 0
                                ? ($stat['total_collected'] / $stat['total_expected']) * 100
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><i class="fas fa-folder"></i>
                                        <?= htmlspecialchars($stat['portfolio_name']) ?></strong>
                                </td>
                                <td><?= number_format($stat['total_clients']) ?></td>
                                <td><?= number_format($stat['total_loans']) ?></td>
                                <td>
                                    <span class="badge badge-pending">
                                        <?= number_format($stat['active_loans']) ?>
                                    </span>
                                </td>
                                <td><?= $currency ?><?= number_format($stat['total_lent'], 2) ?></td>
                                <td><?= $currency ?><?= number_format($stat['total_expected'], 2) ?></td>
                                <td style="color: #10b981; font-weight: bold;">
                                    <?= $currency ?>    <?= number_format($stat['total_collected'], 2) ?>
                                </td>
                                <td style="color: #8b5cf6; font-weight: bold;">
                                    <?= $currency ?>    <?= number_format($stat['total_late_fees_registered'], 2) ?>
                                </td>
                                <td style="color: #f59e0b; font-weight: bold;">
                                    <?= $currency ?>    <?= number_format($stat['pending_balance'], 2) ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div
                                            style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div
                                                style="width: <?= min($recovery_rate, 100) ?>%; background: <?= $recovery_rate >= 75 ? '#10b981' : ($recovery_rate >= 50 ? '#f59e0b' : '#ef4444') ?>; height: 100%;">
                                            </div>
                                        </div>
                                        <span style="font-weight: 600; font-size: 0.85rem; min-width: 45px;">
                                            <?= number_format($recovery_rate, 1) ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($portfolio_stats)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No hay datos de carteras disponibles
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