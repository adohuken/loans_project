<?php
$file = 'index.php';
$content = file_get_contents($file);

// Script JS mejorado con Plugin de Texto Central
$jsContent = "
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
                    data: [<?= \$total_collected_chart ?>, <?= \$total_receivable_chart ?>],
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
                labels: <?= json_encode(\$months) ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?= json_encode(\$incomes) ?>,
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
                    data: [<?= \$total_principal ?>, <?= \$total_interest ?>],
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
";

// Reemplazar todo el bloque de script
$content = preg_replace(
    '/<script>\s*document\.addEventListener.*?<\/script>/s',
    "<script>\n" . $jsContent . "\n</script>",
    $content
);

file_put_contents($file, $content);
echo "Dashboard upgraded to High-End Visuals with Center Text!\n";
?>