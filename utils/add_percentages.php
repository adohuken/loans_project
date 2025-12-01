<?php
$file = 'index.php';
$content = file_get_contents($file);

// Función JS para el tooltip con porcentaje
$tooltipCallback = "
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                
                                const value = context.raw;
                                const dataset = context.dataset;
                                const total = dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = ((value / total) * 100).toFixed(1) + '%';
                                
                                label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(value);
                                label += ' (' + percentage + ')';
                                return label;
                            }
                        }
";

// Reemplazar el callback del tooltip en el gráfico de Progreso de Cobro (Doughnut)
// Buscamos el bloque específico del loanStatusChart
$pattern1 = '/const ctxStatus = document\.getElementById\(\'loanStatusChart\'\).*?callbacks: \{.*?\}\s*\}\s*\}\s*\}\s*}\s*\);/s';
// Como es difícil coincidir con regex exacto en bloque largo, vamos a reemplazar la función de callback anterior
// La anterior era: label += new Intl.NumberFormat... return label;

// Vamos a regenerar el bloque JS completo para los gráficos financieros con la nueva lógica
$jsContent = "
document.addEventListener('DOMContentLoaded', function() {
    // 1. Recovery Chart (Doughnut) - WITH PERCENTAGES
    const ctxStatus = document.getElementById('loanStatusChart');
    if (ctxStatus) {
        const ctx = ctxStatus.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cobrado', 'Por Cobrar'],
                datasets: [{
                    data: [<?= \$total_collected_chart ?>, <?= \$total_receivable_chart ?>],
                    backgroundColor: ['#10b981', '#cbd5e1'],
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
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1) + '%';
                                return label + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(value) + ' (' + percentage + ')';
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

    // 3. Profitability Chart (Pie) - WITH PERCENTAGES
    const ctxFreq = document.getElementById('frequencyChart');
    if (ctxFreq) {
        new Chart(ctxFreq, {
            type: 'pie',
            data: {
                labels: ['Capital Invertido', 'Ganancia (Interés)'],
                datasets: [{
                    data: [<?= \$total_principal ?>, <?= \$total_interest ?>],
                    backgroundColor: ['#3b82f6', '#f59e0b'],
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
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1) + '%';
                                return label + new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(value) + ' (' + percentage + ')';
                            }
                        }
                    }
                }
            }
        });
    }
});
";

// Reemplazar todo el bloque de script
$content = preg_replace(
    '/<script>\s*document\.addEventListener.*?<\/script>/s',
    "<script>\n" . $jsContent . "\n</script>",
    $content
);

file_put_contents($file, $content);
echo "Charts updated with percentages!\n";
?>