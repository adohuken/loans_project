<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Stats
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$active_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();
$paid_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'paid'")->fetchColumn();

// Financial Stats
$total_invested = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?: 0;
$total_expected_profit = $pdo->query("SELECT SUM(total_amount - amount) FROM loans")->fetchColumn() ?: 0;
$total_collected = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0;
$total_receivable = $pdo->query("SELECT SUM(amount_due) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;

// Chart Data: Monthly Income (Last 6 Months)
$monthly_income = $pdo->query("
    SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, SUM(paid_amount) as total
    FROM payments
    WHERE status = 'paid' AND paid_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill missing months with 0
$months = [];
$incomes = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month . '-01'));
    $incomes[] = $monthly_income[$month] ?? 0;
}

$recent_loans = $pdo->query("
    SELECT l.*, c.name, c.cedula 
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    ORDER BY l.id DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($company_name) ?></title>
    <link rel="stylesheet" href="style.css?v=2.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" style="height: 50px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <h1><?= htmlspecialchars($company_name) ?></h1>
            </div>
            <nav>
                <a href="index.php" class="active">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <!-- Financial Dashboard -->
        <div class="grid" style="margin-bottom: 1.5rem;">
            <div class="card" style="border-left: 4px solid #3b82f6;">
                <h3 style="font-size: 0.9rem; color: #64748b;">Total Invertido</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #1e293b;"><?= $currency ?><?= number_format($total_invested, 2) ?></p>
            </div>
            <div class="card" style="border-left: 4px solid #10b981;">
                <h3 style="font-size: 0.9rem; color: #64748b;">Ganancia Esperada</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #10b981;"><?= $currency ?><?= number_format($total_expected_profit, 2) ?></p>
            </div>
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <h3 style="font-size: 0.9rem; color: #64748b;">Total Recaudado</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #8b5cf6;"><?= $currency ?><?= number_format($total_collected, 2) ?></p>
            </div>
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3 style="font-size: 0.9rem; color: #64748b;">Por Cobrar</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #f59e0b;"><?= $currency ?><?= number_format($total_receivable, 2) ?></p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid" style="margin-bottom: 1.5rem; grid-template-columns: 1fr 2fr;">
            <div class="card">
                <h3>Estado de Préstamos</h3>
                <canvas id="loanStatusChart"></canvas>
            </div>
            <div class="card">
                <h3>Ingresos Mensuales (Últimos 6 Meses)</h3>
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Clientes Totales</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--primary);"><?= $total_clients ?></p>
            </div>
            <div class="card">
                <h3>Préstamos Activos</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--success);"><?= $active_loans ?></p>
            </div>
            <div class="card">
                <h3>Acciones Rápidas</h3>
                <div style="margin-top: 1rem;">
                    <a href="create_loan.php" class="btn btn-sm">Nuevo Préstamo</a>
                    <a href="clients.php" class="btn btn-sm btn-secondary">Ver Clientes</a>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Préstamos Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Total a Pagar</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_loans as $loan): ?>
                        <tr>
                            <td>#<?= $loan['id'] ?></td>
                            <td><?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($loan['name']) ?></td>
                            <td><?= $currency ?><?= number_format($loan['amount'], 2) ?></td>
                            <td><?= $currency ?><?= number_format($loan['total_amount'], 2) ?></td>
                            <td><span class="badge badge-<?= $loan['status'] == 'active' ? 'pending' : 'paid' ?>"><?= strtoupper($loan['status']) ?></span></td>
                            <td>
                                <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-secondary">Ver Detalles</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Loan Status Chart (Pie)
        const ctxStatus = document.getElementById('loanStatusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'Pagados'],
                datasets: [{
                    data: [<?= $active_loans ?>, <?= $paid_loans ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Income Chart (Bar)
        const ctxIncome = document.getElementById('incomeChart').getContext('2d');
        new Chart(ctxIncome, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?= json_encode($incomes) ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>

</html>