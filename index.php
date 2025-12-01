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

// Chart Data: Monthly Income (Last 6 Months)
$monthly_income = $pdo->query("
    SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, SUM(paid_amount) as total
    FROM payments
    WHERE status = 'paid' AND paid_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill missing months with 0
$months = [];
$incomes = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month . '-01'));
    $incomes[] = $monthly_income[$month] ?? 0;
}


// Chart Data: Payment Frequencies
$frequencies_data = $pdo->query("
    SELECT payment_frequency, COUNT(*) as count 
    FROM loans 
    GROUP BY payment_frequency
")->fetchAll(PDO::FETCH_KEY_PAIR);

$freq_labels = [];
$freq_counts = [];
$freq_map = [
    "daily" => "Diario",
    "weekly" => "Semanal",
    "biweekly" => "Quincenal",
    "monthly" => "Mensual"
];

foreach ($frequencies_data as $key => $val) {
    $freq_labels[] = $freq_map[$key] ?? ucfirst($key);
    $freq_counts[] = $val;
}



// Chart Data: Financials
// 1. Recovery Progress (Collected vs Receivable)
$total_collected_chart = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0;
$total_receivable_chart = $pdo->query("SELECT SUM(amount_due) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;

// 3. Profitability (Principal vs Interest)
// Aproximación: Total Prestado (Capital) vs (Total a Pagar - Total Prestado) (Interés)
$total_principal = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?: 0;
$total_interest = $pdo->query("SELECT SUM(total_amount - amount) FROM loans")->fetchColumn() ?: 0;


$recent_loans = $pdo->query("
    SELECT l.*, c.name, c.cedula 
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    ORDER BY l.id DESC LIMIT 5
")->fetchAll();
?>

<div class="container">
    <!-- Financial Dashboard -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="card" style="border-left: 4px solid #3b82f6; padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-coins"></i> Total Invertido</h3>
            <p style="font-size: 1.25rem; font-weight: bold; color: #1e293b;">
                <?= $currency ?><?= number_format($total_invested, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #10b981; padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-chart-line"></i> Ganancia Esperada</h3>
            <p style="font-size: 1.25rem; font-weight: bold; color: #10b981;">
                <?= $currency ?><?= number_format($total_expected_profit, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #8b5cf6; padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-wallet"></i> Total Recaudado</h3>
            <p style="font-size: 1.25rem; font-weight: bold; color: #8b5cf6;">
                <?= $currency ?><?= number_format($total_collected, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #f59e0b; padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-hourglass-half"></i> Por Cobrar</h3>
            <p style="font-size: 1.25rem; font-weight: bold; color: #f59e0b;">
                <?= $currency ?><?= number_format($total_receivable, 2) ?>
            </p>
        </div>
        <div class="card" style="border-left: 4px solid #ef4444; padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-exclamation-circle"></i> Mora Cobrada</h3>
            <p style="font-size: 1.25rem; font-weight: bold; color: #ef4444;">
                <?= $currency ?><?= number_format($total_late_fees, 2) ?>
            </p>
        </div>
    </div>

    
    
    <!-- Charts Section (3 Columns) -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- 1. Loan Status -->
        <div class="card">
            <h3><i class="fas fa-chart-pie"></i> Progreso de Cobro</h3>
            <div style="max-height: 250px; position: relative;">
                <canvas id="loanStatusChart"></canvas>
            </div>
        </div>
        
        <!-- 2. Monthly Income -->
        <div class="card">
            <h3><i class="fas fa-chart-bar"></i> Ingresos (12 Meses)</h3>
            <div style="max-height: 250px; position: relative;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <!-- 3. Payment Frequency (New) -->
        <div class="card">
            <h3><i class="fas fa-coins"></i> Estructura de Capital</h3>
            <div style="max-height: 250px; position: relative;">
                <canvas id="frequencyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <div class="card">
            <h3><i class="fas fa-users"></i> Clientes Totales</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary);"><?= $total_clients ?></p>
        </div>
        <div class="card">
            <h3><i class="fas fa-file-invoice-dollar"></i> Préstamos Activos</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--success);"><?= $active_loans ?></p>
        </div>
        <div class="card">
            <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
            <div style="margin-top: 1rem;">
                <a href="create_loan.php" class="btn btn-sm"><i class="fas fa-plus"></i> Nuevo Préstamo</a>
                <a href="clients.php" class="btn btn-sm btn-secondary"><i class="fas fa-list"></i> Ver Clientes</a>
            </div>
        </div>
    </div>
        <div class="card">
            <h3><i class="fas fa-users"></i> Clientes Totales</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary);"><?= $total_clients ?></p>
        </div>
        <div class="card">
            <h3><i class="fas fa-file-invoice-dollar"></i> Préstamos Activos</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--success);"><?= $active_loans ?></p>
        </div>
        <div class="card">
            <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
            <div style="margin-top: 1rem;">
                <a href="create_loan.php" class="btn btn-sm"><i class="fas fa-plus"></i> Nuevo Préstamo</a>
                <a href="clients.php" class="btn btn-sm btn-secondary"><i class="fas fa-list"></i> Ver Clientes</a>
            </div>
        </div>
    </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-history"></i> Préstamos Recientes</h2>
        <div class="table-responsive">
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
                            <td><span
                                    class="badge badge-<?= $loan['status'] == 'active' ? 'pending' : 'paid' ?>"><?= $loan['status'] == 'active' ? 'ACTIVO' : 'PAGADO' ?></span>
                            </td>
                            <td>
                                <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-secondary"><i
                                        class="fas fa-eye"></i> Ver
                                    Detalles</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
    
    // Plugin para texto en el centro (Doughnut)
    const centerTextPlugin = {
        id: 'centerText',
        beforeDraw: function(chart) {
            if (chart.config.type !== 'doughnut') return;
            
            var width = chart.width,
                height = chart.height,
                ctx = chart.ctx;

            ctx.restore();
            var fontSize = (height / 100).toFixed(2);
            ctx.font = 'bold ' + fontSize + 'em Inter, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#1e293b';

            // Calcular porcentaje de cobro
            var data = chart.data.datasets[0].data;
            var total = data.reduce((a, b) => a + b, 0);
            var value = data[0]; // Cobrado
            var percentage = total > 0 ? Math.round((value / total) * 100) + '%' : '0%';

            var text = percentage,
                textX = Math.round((width - ctx.measureText(text).width) / 2),
                textY = height / 2;

            ctx.fillText(text, textX, textY);
            
            // Texto pequeño debajo
            ctx.font = 'normal ' + (fontSize * 0.4).toFixed(2) + 'em Inter, sans-serif';
            ctx.fillStyle = '#64748b';
            var subtext = 'Recuperado';
            var subtextX = Math.round((width - ctx.measureText(subtext).width) / 2);
            ctx.fillText(subtext, subtextX, textY + (height * 0.15));
            
            ctx.save();
        }
    };

    // Registrar el plugin
    Chart.register(centerTextPlugin);

    // 1. Recovery Chart (Doughnut) - VISUAL IMPACT
    const ctxStatus = document.getElementById('loanStatusChart');
    if (ctxStatus) {
        const ctx = ctxStatus.getContext('2d');
        // Gradiente Verde Vibrante
        const greenGradient = ctx.createLinearGradient(0, 0, 0, 300);
        greenGradient.addColorStop(0, '#34d399');
        greenGradient.addColorStop(1, '#059669');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cobrado', 'Por Cobrar'],
                datasets: [{
                    data: [<?= $total_collected_chart ?>, <?= $total_receivable_chart ?>],
                    backgroundColor: [greenGradient, '#f1f5f9'],
                    borderWidth: 0,
                    hoverOffset: 5,
                    borderRadius: 20 // Bordes muy redondeados
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '85%', // Anillo más fino y elegante
                plugins: {
                    legend: { display: false }, // Ocultamos leyenda para limpieza visual
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1) + '%';
                                return context.label + ': ' + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(value) + ' (' + percentage + ')';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Income Chart (Bar) - CLEAN & MODERN
    const ctxIncome = document.getElementById('incomeChart');
    if (ctxIncome) {
        const ctx = ctxIncome.getContext('2d');
        const barGradient = ctx.createLinearGradient(0, 0, 0, 400);
        barGradient.addColorStop(0, '#818cf8');
        barGradient.addColorStop(1, '#4f46e5');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?= json_encode($incomes) ?>,
                    backgroundColor: barGradient,
                    borderRadius: 6,
                    borderSkipped: false, // Barras flotantes redondeadas completas
                    barThickness: 25
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { display: true, drawBorder: false, color: '#f8fafc', lineWidth: 2 }, 
                        ticks: { callback: v => '$' + v, font: { weight: 'bold', size: 11 }, color: '#94a3b8' },
                        border: { display: false }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#94a3b8' }
                    }
                }
            }
        });
    }

    // 3. Profitability Chart (Pie) - VIBRANT
    const ctxFreq = document.getElementById('frequencyChart');
    if (ctxFreq) {
        const ctx = ctxFreq.getContext('2d');
        // Gradiente Azul
        const blueGradient = ctx.createLinearGradient(0, 0, 0, 300);
        blueGradient.addColorStop(0, '#60a5fa');
        blueGradient.addColorStop(1, '#2563eb');
        // Gradiente Naranja
        const orangeGradient = ctx.createLinearGradient(0, 0, 0, 300);
        orangeGradient.addColorStop(0, '#fbbf24');
        orangeGradient.addColorStop(1, '#d97706');

        new Chart(ctxFreq, {
            type: 'pie',
            data: {
                labels: ['Capital', 'Ganancia'],
                datasets: [{
                    data: [<?= $total_principal ?>, <?= $total_interest ?>],
                    backgroundColor: [blueGradient, orangeGradient],
                    borderWidth: 4,
                    borderColor: '#ffffff', // Separador blanco limpio
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { weight: '600' } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1) + '%';
                                return context.label + ': ' + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(value) + ' (' + percentage + ')';
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