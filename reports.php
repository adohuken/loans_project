<?php
require 'auth.php';
require 'db.php';

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// 1. Total Lent in Range
$stmt = $pdo->prepare("SELECT SUM(amount) FROM loans WHERE start_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_lent = $stmt->fetchColumn() ?: 0;

// 2. Total Collected in Range
$stmt = $pdo->prepare("SELECT SUM(paid_amount) FROM payments WHERE paid_date BETWEEN ? AND ? AND status = 'paid'");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$total_collected = $stmt->fetchColumn() ?: 0;

// 3. Net Profit (Approximate: Total Collected - Principal Portion)
// This is a simplified calculation. Ideally, you'd track interest paid separately.
// For now, let's calculate Profit based on Closed Loans in this period to be more accurate, 
// OR just show "Expected Profit" from loans created in this period.
// Let's stick to "Revenue" (Interest Collected) if possible, but without separate interest tracking, 
// we can show "Total Collected" and "Total Lent" as the main cash flow indicators.

// Let's add "Outstanding Balance" (Total currently owed by everyone)
$total_outstanding = $pdo->query("SELECT SUM(amount_due) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;

// 4. Loan Status Distribution (All time)
$status_counts = $pdo->query("SELECT status, COUNT(*) as count FROM loans GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$active_count = $status_counts['active'] ?? 0;
$paid_count = $status_counts['paid'] ?? 0;

// 5. Monthly Income (Chart Data - Last 12 Months)
$monthly_income = $pdo->query("
    SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, SUM(paid_amount) as total
    FROM payments
    WHERE status = 'paid' AND paid_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$months = [];
$incomes = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month . '-01'));
    $incomes[] = $monthly_income[$month] ?? 0;
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
    <link rel="stylesheet" href="style.css?v=2.1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <header>
            <h1><?= htmlspecialchars($company_name) ?></h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="reports.php" class="active">Reportes</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Salir</a>
            </nav>
        </header>

        <div class="card no-print">
            <h2>Filtrar Reporte</h2>
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label>Fecha Inicio</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label>Fecha Fin</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>">
                </div>
                <button type="submit" class="btn">Filtrar</button>
                <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir / PDF</button>
            </form>
        </div>

        <div class="grid" style="margin-top: 2rem;">
            <div class="card" style="border-left: 4px solid #3b82f6;">
                <h3>Total Prestado</h3>
                <small class="text-muted">En el periodo seleccionado</small>
                <p style="font-size: 2rem; font-weight: bold; color: #1e293b;">
                    <?= $currency ?><?= number_format($total_lent, 2) ?></p>
            </div>
            <div class="card" style="border-left: 4px solid #10b981;">
                <h3>Total Recaudado</h3>
                <small class="text-muted">En el periodo seleccionado</small>
                <p style="font-size: 2rem; font-weight: bold; color: #10b981;">
                    <?= $currency ?><?= number_format($total_collected, 2) ?></p>
            </div>
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3>Saldo Pendiente Total</h3>
                <small class="text-muted">De todos los préstamos activos</small>
                <p style="font-size: 2rem; font-weight: bold; color: #f59e0b;">
                    <?= $currency ?><?= number_format($total_outstanding, 2) ?></p>
            </div>
        </div>

        <div class="grid" style="margin-top: 2rem; grid-template-columns: 2fr 1fr;">
            <div class="card">
                <h3>Ingresos Mensuales (Últimos 12 Meses)</h3>
                <div style="height: 300px;">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h3>Estado de la Cartera</h3>
                <div style="height: 300px; display: flex; justify-content: center;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Income Chart
        new Chart(document.getElementById('incomeChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?= json_encode($incomes) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'Pagados'],
                datasets: [{
                    data: [<?= $active_count ?>, <?= $paid_count ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>

</html>