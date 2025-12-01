<?php
$file = 'index.php';
$content = file_get_contents($file);

// 1. Agregar consulta de datos para el nuevo gráfico (Frecuencia de Pagos)
$newDataQuery = '
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
';

// Insertar la consulta después de la de ingresos
$content = str_replace(
    '$recent_loans = $pdo->query("',
    $newDataQuery . "\n\n" . '$recent_loans = $pdo->query("',
    $content
);

// 2. Nuevo Layout HTML: 3 Gráficos en línea
$chartsBlock = '
    <!-- Charts Section (3 Columns) -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- 1. Loan Status -->
        <div class="card">
            <h3><i class="fas fa-chart-pie"></i> Estado de Préstamos</h3>
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
            <h3><i class="fas fa-chart-pie"></i> Tipos de Préstamos</h3>
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
    </div>';

// Reemplazar la sección de gráficos anterior (buscamos el bloque grande que pusimos antes)
$pattern = '/<!-- Income Chart Section \(Full Width\) -->.*?<div class="grid".*?<\/div>\s*<\/div>\s*<\/div>/s';
// Nota: El patrón anterior puede ser complejo de coincidir exactamente si hubo cambios manuales.
// Vamos a intentar reemplazar todo desde el final de las "Financial Stats" hasta "Préstamos Recientes"
$pattern = '/<\/div>\s*<!-- Income Chart Section \(Full Width\) -->.*?<div class="card">\s*<h2><i class="fas fa-history">/s';

// Si el patrón anterior falla, usamos uno más genérico basado en el contenido actual
$content = preg_replace(
    '/<!-- Income Chart Section \(Full Width\) -->.*?<!-- Stats & Pie Chart Grid -->.*?<\/div>\s*<\/div>/s',
    $chartsBlock,
    $content
);


// 3. Actualizar JavaScript para incluir el 3er gráfico
$jsContent = "
document.addEventListener('DOMContentLoaded', function() {
    // 1. Loan Status Chart (Doughnut)
    const ctxStatus = document.getElementById('loanStatusChart');
    if (ctxStatus) {
        const ctx = ctxStatus.getContext('2d');
        const activeGradient = ctx.createLinearGradient(0, 0, 0, 300);
        activeGradient.addColorStop(0, '#fbbf24');
        activeGradient.addColorStop(1, '#f59e0b');
        const paidGradient = ctx.createLinearGradient(0, 0, 0, 300);
        paidGradient.addColorStop(0, '#34d399');
        paidGradient.addColorStop(1, '#10b981');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'Pagados'],
                datasets: [{
                    data: [<?= \$active_loans ?>, <?= \$paid_loans ?>],
                    backgroundColor: [activeGradient, paidGradient],
                    borderWidth: 0,
                    hoverOffset: 10,
                    borderRadius: 5,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }
                }
            }
        });
    }

    // 2. Income Chart (Bar)
    const ctxIncome = document.getElementById('incomeChart');
    if (ctxIncome) {
        const ctx = ctxIncome.getContext('2d');
        const barGradient = ctx.createLinearGradient(0, 0, 0, 300);
        barGradient.addColorStop(0, '#818cf8');
        barGradient.addColorStop(1, '#4f46e5');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(\$months) ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?= json_encode(\$incomes) ?>,
                    backgroundColor: barGradient,
                    borderRadius: 4,
                    barThickness: 'flex',
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: true, drawBorder: false }, ticks: { callback: v => '$' + v } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 3. Frequency Chart (Polar Area) - NEW
    const ctxFreq = document.getElementById('frequencyChart');
    if (ctxFreq) {
        new Chart(ctxFreq, {
            type: 'polarArea',
            data: {
                labels: <?= json_encode(\$freq_labels) ?>,
                datasets: [{
                    data: <?= json_encode(\$freq_counts) ?>,
                    backgroundColor: [
                        'rgba(244, 63, 94, 0.7)',  // Rose
                        'rgba(59, 130, 246, 0.7)', // Blue
                        'rgba(16, 185, 129, 0.7)', // Emerald
                        'rgba(245, 158, 11, 0.7)'  // Amber
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }
                },
                scales: {
                    r: { ticks: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' } }
                }
            }
        });
    }
});
";

$content = preg_replace(
    '/<script>\s*document\.addEventListener.*?<\/script>/s',
    "<script>\n" . $jsContent . "\n</script>",
    $content
);

file_put_contents($file, $content);
echo "Dashboard updated: 3 Charts in one line!\n";
?>