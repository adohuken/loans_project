<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Fetch Settings for Currency
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

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
            COUNT(DISTINCT CASE WHEN l.status = 'active' AND (l.total_amount - (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE loan_id = l.id)) > 0.05 THEN l.id ELSE NULL END) as active_loans,
            COALESCE((SELECT SUM(l2.amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id = p.id)), 0) as total_lent,
            COALESCE((SELECT SUM(l2.total_amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id = p.id)), 0) as total_expected,
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
            COUNT(DISTINCT CASE WHEN l.status = 'active' AND (l.total_amount - (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE loan_id = l.id)) > 0.05 THEN l.id ELSE NULL END) as active_loans,
            COALESCE((SELECT SUM(l2.amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id IS NULL)), 0) as total_lent,
            COALESCE((SELECT SUM(l2.total_amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id IS NULL)), 0) as total_expected,
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
            COUNT(DISTINCT CASE WHEN l.status = 'active' AND (l.total_amount - (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE loan_id = l.id)) > 0.05 THEN l.id ELSE NULL END) as active_loans,
            COALESCE((SELECT SUM(l2.amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id = p.id)), 0) as total_lent,
            COALESCE((SELECT SUM(l2.total_amount) FROM loans l2 WHERE l2.client_id IN (SELECT c2.id FROM clients c2 WHERE c2.portfolio_id = p.id)), 0) as total_expected,
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

// Include enhanced header
require 'components/enhanced_header.php';
?>

<style>
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .stat-card {
        background: var(--primary-surface);
        padding: 1.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-top: 0.25rem;
    }

    .stat-desc {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: 0.5rem;
    }

    /* Filter Bar */
    .filter-bar {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }

    /* Table */
    .table-responsive {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .modern-table tr:hover td {
        background: var(--secondary-surface);
    }

    .badge-modern {
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
    }

    /* Print Styles */
    @media print {
        body {
            background: white;
        }

        .no-print {
            display: none !important;
        }

        .container {
            padding: 0;
            max-width: 100%;
        }

        .card,
        .stat-card {
            box-shadow: none;
            border: 1px solid #ccc;
            break-inside: avoid;
        }
    }
</style>

<div class="container">

    <!-- Filter Section -->
    <div class="card no-print">
        <div class="card-body">
            <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--text-primary);">
                <i class="fas fa-filter" style="color: var(--primary-color)"></i> Filtrar Reporte
            </h2>
            <form method="GET" class="filter-bar">
                <div style="flex: 1; min-width: 200px;">
                    <div class="form-group">
                        <label>Cartera</label>
                        <select name="portfolio" class="form-control">
                            <option value="all" <?= $portfolio_filter === 'all' ? 'selected' : '' ?>>Todas las Carteras
                            </option>
                            <?php foreach ($portfolios as $portfolio): ?>
                                <option value="<?= $portfolio['id'] ?>" <?= $portfolio_filter == $portfolio['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($portfolio['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control">
                    </div>
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control">
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label style="visibility: hidden;">Acciones</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                            <button type="button" onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-icon" style="background: #e0f2fe; color: var(--primary-color);">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-label">Total Prestado</div>
                <div class="stat-value"><?= $currency ?><?= number_format($total_lent, 2) ?></div>
            </div>
            <div class="stat-desc">En el periodo seleccionado</div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-icon" style="background: #d1fae5; color: var(--success-color);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-label">Total Recaudado</div>
                <div class="stat-value" style="color: var(--success-color);">
                    <?= $currency ?><?= number_format($total_collected, 2) ?>
                </div>
            </div>
            <div class="stat-desc">Capital + Intereses cobrados</div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-icon" style="background: #fef3c7; color: var(--warning-color);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-label">Saldo Pendiente</div>
                <div class="stat-value" style="color: var(--warning-color);">
                    <?= $currency ?><?= number_format($total_outstanding, 2) ?>
                </div>
            </div>
            <div class="stat-desc">Capital + Mora pendiente global</div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-icon" style="background: #ede9fe; color: var(--info-color);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Ganancia por Mora</div>
                <div class="stat-value" style="color: var(--info-color);">
                    <?= $currency ?><?= number_format($total_late_fees, 2) ?>
                </div>
            </div>
            <div class="stat-desc">Total de moras generadas</div>
        </div>
    </div>

    <!-- Portfolio Statistics -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-body">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-briefcase" style="color: var(--text-secondary)"></i> Estadísticas por Cartera
            </h2>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Cartera</th>
                            <th>Clientes</th>
                            <th>Prést. Activos</th>
                            <th>Total Prestado</th>
                            <th>Recaudado</th>
                            <th>Mora Reg.</th>
                            <th>Saldo Capital</th>
                            <th>Recuperación</th>
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
                                    <strong><?= htmlspecialchars($stat['portfolio_name']) ?></strong>
                                </td>
                                <td><?= number_format($stat['total_clients']) ?></td>
                                <td>
                                    <span class="badge-modern" style="background: #f1f5f9; color: var(--text-secondary);">
                                        <?= number_format($stat['active_loans']) ?>
                                    </span>
                                </td>
                                <td><?= $currency ?><?= number_format($stat['total_lent'], 2) ?></td>
                                <td style="color: var(--success-color); font-weight: 600;">
                                    <?= $currency ?>     <?= number_format($stat['total_collected'], 2) ?>
                                </td>
                                <td style="color: var(--info-color); font-weight: 600;">
                                    <?= $currency ?>     <?= number_format($stat['total_late_fees_registered'], 2) ?>
                                </td>
                                <td style="color: var(--warning-color); font-weight: 600;">
                                    <?= $currency ?>     <?= number_format($stat['pending_balance'], 2) ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div
                                            style="flex: 1; background: #e5e7eb; height: 6px; border-radius: 4px; overflow: hidden; width: 60px;">
                                            <div
                                                style="width: <?= min($recovery_rate, 100) ?>%; background: <?= $recovery_rate >= 75 ? '#10b981' : ($recovery_rate >= 50 ? '#f59e0b' : '#ef4444') ?>; height: 100%;">
                                            </div>
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-secondary);">
                                            <?= number_format($recovery_rate, 1) ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($portfolio_stats)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No hay datos de carteras disponibles
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Transactions -->
    <?php
    // Fetch Transactions for Detailed View
    if ($portfolio_filter === 'all') {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as client_name, c.portfolio_id, port.name as portfolio_name, l.total_amount as loan_total, l.id as loan_id
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN clients c ON l.client_id = c.id
            LEFT JOIN portfolios port ON c.portfolio_id = port.id
            WHERE p.paid_date BETWEEN ? AND ? 
            AND p.status = 'paid'
            ORDER BY p.paid_date DESC
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as client_name, c.portfolio_id, port.name as portfolio_name, l.total_amount as loan_total, l.id as loan_id
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN clients c ON l.client_id = c.id
            LEFT JOIN portfolios port ON c.portfolio_id = port.id
            WHERE p.paid_date BETWEEN ? AND ? 
            AND p.status = 'paid'
            AND c.portfolio_id = ?
            ORDER BY p.paid_date DESC
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $portfolio_filter]);
    }
    $transactions = $stmt->fetchAll();

    // Calculate total transaction value just in case it's needed for logic, though often paid_amount + paid_late_fee is fine to do inline
    foreach ($transactions as &$t) {
        $t['total_transaction'] = $t['paid_amount'] + $t['paid_late_fee'];
        // Ensure portfolio name handles null
        if (empty($t['portfolio_name'])) {
            $t['portfolio_name'] = 'Sin Asignar';
        }
    }
    unset($t);
    ?>

    <?php
    // Determine selected portfolio name for display
    $selected_portfolio_name = 'General';
    if ($portfolio_filter !== 'all') {
        foreach ($portfolios as $p) {
            if ($p['id'] == $portfolio_filter) {
                $selected_portfolio_name = $p['name'];
                break;
            }
        }
    }
    ?>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0;">
                    <i class="fas fa-table" style="color: var(--text-secondary)"></i> Hoja de Datos Detallada -
                    <?= htmlspecialchars($selected_portfolio_name) ?>
                </h2>
                <button onclick="exportTableToCSV('reporte_movimientos.csv')" class="btn btn-secondary"
                    style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>

            <div class="table-responsive">
                <table id="transactionsTable" class="modern-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Cartera</th>
                            <th>Préstamo</th>
                            <th>Capital/Int.</th>
                            <th>Mora</th>
                            <th>Total Recibido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($t['paid_date'])) ?> <span
                                        style="color: #94a3b8; font-size: 0.8em;"><?= date('H:i', strtotime($t['paid_date'])) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($t['client_name']) ?></strong></td>
                                <td><span class="badge-modern"
                                        style="background: #f1f5f9; color: #64748b;"><?= htmlspecialchars($t['portfolio_name']) ?></span>
                                </td>
                                <td><span style="font-family: monospace;">#<?= $t['loan_id'] ?></span></td>
                                <td><?= $currency ?><?= number_format($t['paid_amount'], 2) ?></td>
                                <td><?= $currency ?><?= number_format($t['paid_late_fee'], 2) ?></td>
                                <td style="font-weight: 700; color: var(--success-color);">
                                    <?= $currency ?>     <?= number_format($t['total_transaction'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                                    No hay movimientos registrados en este periodo
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function exportTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll("#transactionsTable tr");

        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");

            for (var j = 0; j < cols.length; j++)
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');

            csv.push(row.join(","));
        }

        downloadCSV(csv.join("\n"), filename);
    }

    function downloadCSV(csv, filename) {
        var csvFile;
        var downloadLink;

        csvFile = new Blob([csv], { type: "text/csv" });
        downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
</script>
</body>

</html>