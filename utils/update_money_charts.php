<?php
$file = 'index.php';
$content = file_get_contents($file);

// 1. Agregar consultas financieras adicionales
$financialQueries = '
// Chart Data: Financials
// 1. Recovery Progress (Collected vs Receivable)
$total_collected_chart = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE status = \'paid\'")->fetchColumn() ?: 0;
$total_receivable_chart = $pdo->query("SELECT SUM(amount_due) FROM payments WHERE status = \'pending\'")->fetchColumn() ?: 0;

// 3. Profitability (Principal vs Interest)
// Aproximación: Total Prestado (Capital) vs (Total a Pagar - Total Prestado) (Interés)
$total_principal = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?: 0;
$total_interest = $pdo->query("SELECT SUM(total_amount - amount) FROM loans")->fetchColumn() ?: 0;
';

// Insertar las consultas antes de $recent_loans
$content = str_replace(
    '$recent_loans = $pdo->query("',
    $financialQueries . "\n\n" . '$recent_loans = $pdo->query("',
    $content
);

// 2. Actualizar Títulos de los Gráficos en HTML
$content = str_replace(
    '<h3><i class="fas fa-chart-pie"></i> Estado de Préstamos</h3>',
    '<h3><i class="fas fa-chart-pie"></i> Progreso de Cobro</h3>',
    $content
);

$content = str_replace(
    '<h3><i class="fas fa-chart-pie"></i> Tipos de Préstamos</h3>',
    '<h3><i class="fas fa-coins"></i> Estructura de Capital</h3>',
    $content
);

// 3. Actualizar JavaScript con los nuevos datos financieros
$jsContent = "
document.addEventListener('DOMContentLoaded', function() {
    // 1. Recovery Chart (Doughnut) - MONEY FOCUSED
    const ctxStatus = document.getElementById('loanStatusChart');
    if (ctxStatus) {
        const ctx = ctxStatus.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cobrado', 'Por Cobrar'],
                datasets: [{
                    data: [<?= \$total_collected_chart ?>, <?= \$total_receivable_chart ?>],
                    backgroundColor: ['#10b981', '#cbd5e1'], // Green (Money in) vs Grey (Pending)
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(context.raw);
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Income Chart (Bar) - EXISTING
    const ctxIncome = document.getElementById('incomeChart');
    if (ctxIncome) {
        const ctx = ctxIncome.getContext('2d');
        const barGradient = ctx.createLinearGradient(0, 0, 0, 300);
        barGradient.addColorStop(0, '#6366f1');
        barGradient.addColorStop(1, '#4338ca');
        
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
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Ingresos: ' + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { display: true, drawBorder: false, color: '#f1f5f9' }, 
                        ticks: { callback: v => '$' + v } 
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 3. Profitability Chart (Pie) - NEW MONEY CHART
    const ctxFreq = document.getElementById('frequencyChart');
    if (ctxFreq) {
        new Chart(ctxFreq, {
            type: 'pie',
            data: {
                labels: ['Capital Invertido', 'Ganancia (Interés)'],
                datasets: [{
                    data: [<?= \$total_principal ?>, <?= \$total_interest ?>],
                    backgroundColor: [
                        '#3b82f6', // Blue (Principal)
                        '#f59e0b'  // Amber (Profit)
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(context.raw);
                                return label;
                            }
                        }
                    }
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
echo "Dashboard updated with Money-Focused Charts!\n";
?>