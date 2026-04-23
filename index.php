<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// Include enhanced header
require 'components/enhanced_header.php';

// Stats
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$active_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();
$paid_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'paid'")->fetchColumn();

// Financial Stats
$total_invested = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?: 0;
$total_expected_profit = $pdo->query("SELECT SUM(total_amount - amount) FROM loans")->fetchColumn() ?: 0;
$total_collected = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0;
$total_receivable = $pdo->query("SELECT SUM(amount_due) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;
$total_late_fees = $pdo->query("SELECT SUM(paid_late_fee) FROM payments")->fetchColumn() ?: 0;

$recent_loans = $pdo->query("
    SELECT l.*, c.name, c.cedula 
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    ORDER BY l.id DESC LIMIT 5
")->fetchAll();

// Chart Data: Financials & History
// 1. Monthly Income (Last 12 Months)
// Chart Data: Financials & History
// 1. Monthly Cash Flow (Last 6 Months: Lended vs Collected)
$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(date_column, '%Y-%m') as month,
        SUM(total_lended) as lended,
        SUM(total_collected) as collected
    FROM (
        SELECT start_date as date_column, amount as total_lended, 0 as total_collected FROM loans
        UNION ALL
        SELECT paid_date as date_column, 0 as total_lended, paid_amount as total_collected FROM payments WHERE status = 'paid'
    ) as combined
    WHERE date_column >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$lended_data = [];
$collected_data = [];

// Initialize last 6 months to 0
for ($i = 5; $i >= 0; $i--) {
    $m_key = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($m_key . '-01'));

    // Find matching data
    $found = false;
    foreach ($monthly_stats as $stat) {
        if ($stat['month'] === $m_key) {
            $lended_data[] = (float) $stat['lended'];
            $collected_data[] = (float) $stat['collected'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $lended_data[] = 0;
        $collected_data[] = 0;
    }
}

// 2. Portfolio Health (Active vs Paid vs Past Due)
// Note: 'Past Due' logic depends on due dates, simplifying to Active vs Paid for robustness if no complex logic exists
$portfolio_active = (int) $active_loans;
$portfolio_paid = (int) $paid_loans;


?>

<style>
    /* ... (Styles unchanged) ... */
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
        padding: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card h2,
    .card h3 {
        color: var(--text-primary);
        margin-bottom: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card h2 {
        font-size: 1.25rem;
    }

    .card h3 {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    /* Grid Layouts */
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Modern Table */
    .table-responsive {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--bg-tertiary);
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

    /* Badges */
    .badge {
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: inline-block;
    }

    .badge-active {
        background: var(--accent-light);
        color: var(--accent-primary);
        border: 1px solid var(--accent-primary);
    }

    .badge-paid {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid #10b981;
    }

    .badge-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid #f59e0b;
    }

    /* Yellow for Pending */

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s;
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
        background: var(--secondary-surface);
        color: var(--text-primary);
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
</style>

<div class="container">
    <!-- Financial Dashboard -->
    <div class="dashboard-stats">
        <div class="card" style="border-left: 4px solid #3b82f6;">
            <h3><i class="fas fa-coins"></i> Total Invertido</h3>
            <p style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">
                <?= $currency ?><?= number_format($total_invested, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #10b981;">
            <h3><i class="fas fa-chart-line"></i> Ganancia Esperada</h3>
            <p style="font-size: 1.5rem; font-weight: 800; color: #10b981;">
                <?= $currency ?><?= number_format($total_expected_profit, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #8b5cf6;">
            <h3><i class="fas fa-wallet"></i> Total Recaudado</h3>
            <p style="font-size: 1.5rem; font-weight: 800; color: #8b5cf6;">
                <?= $currency ?><?= number_format($total_collected, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #f59e0b;">
            <h3><i class="fas fa-hourglass-half"></i> Por Cobrar</h3>
            <p style="font-size: 1.5rem; font-weight: 800; color: #f59e0b;">
                <?= $currency ?><?= number_format($total_receivable, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #ef4444;">
            <h3><i class="fas fa-exclamation-circle"></i> Mora Cobrada</h3>
            <p style="font-size: 1.5rem; font-weight: 800; color: #ef4444;">
                <?= $currency ?><?= number_format($total_late_fees, 2) ?>
            </p>
        </div>
    </div>



    <!-- Quick Stats & Actions -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="card" style="align-items: center; text-align: center;">
            <div
                style="width: 50px; height: 50px; background: var(--accent-lighter); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-primary); margin-bottom: 1rem;">
                <i class="fas fa-users" style="font-size: 1.5rem;"></i>
            </div>
            <h3 style="margin: 0; color: var(--text-primary);">Clientes Totales</h3>
            <p style="font-size: 2rem; font-weight: 800; color: var(--text-primary); margin: 0.5rem 0;">
                <?= $total_clients ?>
            </p>
        </div>

        <div class="card" style="align-items: center; text-align: center;">
            <div
                style="width: 50px; height: 50px; background: var(--accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-primary); margin-bottom: 1rem;">
                <i class="fas fa-file-invoice-dollar" style="font-size: 1.5rem;"></i>
            </div>
            <h3 style="margin: 0; color: var(--text-primary);">Préstamos Activos</h3>
            <p style="font-size: 2rem; font-weight: 800; color: var(--text-primary); margin: 0.5rem 0;">
                <?= $active_loans ?>
            </p>
        </div>

        <div class="card" style="justify-content: center;">
            <h2 style="font-size: 1.1rem; margin-bottom: 1.5rem;"><i class="fas fa-bolt"
                    style="color: var(--warning-color);"></i> Acciones Rápidas</h2>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="create_loan.php" class="btn btn-primary" style="justify-content: center;">
                    <i class="fas fa-plus-circle"></i> Nuevo Préstamo
                </a>
                <a href="clients.php" class="btn btn-secondary" style="justify-content: center;">
                    <i class="fas fa-users"></i> Ver Clientes
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Loans Table -->
    <div class="card">
        <h2><i class="fas fa-history" style="color: var(--text-secondary);"></i> Préstamos Recientes</h2>
        <div class="table-responsive">
            <table class="modern-table">
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
                            <td><span
                                    style="font-family: monospace; color: var(--text-secondary);">#<?= $loan['id'] ?></span>
                            </td>
                            <td><?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?></td>
                            <td><strong><?= htmlspecialchars($loan['name']) ?></strong></td>
                            <td><?= $currency ?><?= number_format($loan['amount'], 2) ?></td>
                            <td><?= $currency ?><?= number_format($loan['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $loan['status'] == 'active' ? 'active' : 'paid' ?>">
                                    <?= $loan['status'] == 'active' ? 'ACTIVO' : 'PAGADO' ?>
                                </span>
                            </td>
                            <td>
                                <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i> Detalles
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Section (New Robust Layout) -->
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem; align-items: start;">
        <!-- 1. Cash Flow Trends -->
        <div class="card" style="min-height: 400px; height: 400px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-chart-area" style="color: var(--primary-color);"></i> Flujo de Caja</h2>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Últimos 6 Meses</span>
            </div>
            <div style="flex-grow: 1; position: relative; width: 100%; height: 100%; overflow: hidden;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

        <!-- 2. Portfolio Status -->
        <div class="card" style="min-height: 400px; height: 400px;">
            <div style="margin-bottom: 1rem;">
                <h2><i class="fas fa-chart-pie" style="color: var(--info-color);"></i> Estado de Cartera</h2>
            </div>
            <div
                style="flex-grow: 1; position: relative; width: 100%; height: 100%; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <canvas id="portfolioChart"></canvas>
            </div>
        </div>
    </div>
</div>










<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = '#64748b';

        // 1. Cash Flow Chart (Bar + Line)
        const ctxFlow = document.getElementById('cashFlowChart');
        if (ctxFlow) {
            new Chart(ctxFlow, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Cobrado',
                            data: <?= json_encode($collected_data) ?>,
                            backgroundColor: '#10b981',
                            borderRadius: 4,
                            order: 2
                        },
                        {
                            type: 'line',
                            label: 'Prestado',
                            data: <?= json_encode($lended_data) ?>,
                            borderColor: '#3b82f6',
                            borderWidth: 3,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
                            pointRadius: 4,
                            tension: 0.3,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: {
                            backgroundColor: 'rgba(30, 41, 59, 0.95)',
                            padding: 12,
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [2, 4], color: '#f1f5f9' },
                            ticks: { callback: v => '$' + v, font: { size: 11 } },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 2. Portfolio Status (Doughnut)
        const ctxPortfolio = document.getElementById('portfolioChart');
        if (ctxPortfolio) {
            new Chart(ctxPortfolio, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Pagados'],
                    datasets: [{
                        data: [<?= $portfolio_active ?>, <?= $portfolio_paid ?>],
                        backgroundColor: ['#3b82f6', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                        tooltip: {
                            backgroundColor: 'rgba(30, 41, 59, 0.95)',
                            callbacks: {
                                label: function (context) {
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                                    return context.label + ': ' + value + ' (' + percentage + ')';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
</body>

</html>