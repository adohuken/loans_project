<?php
$file = 'index.php';
$content = file_get_contents($file);

// Script para cambiar el gráfico de Frecuencia a RADAR
$jsContent = "
    // 3. Frequency Chart (Radar) - OPTION 1
    const ctxFreq = document.getElementById('frequencyChart');
    if (ctxFreq) {
        const freqGradient = ctxFreq.getContext('2d').createRadialGradient(150, 150, 0, 150, 150, 200);
        freqGradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
        freqGradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctxFreq, {
            type: 'radar',
            data: {
                labels: <?= json_encode(\$freq_labels) ?>,
                datasets: [{
                    label: 'Frecuencia',
                    data: <?= json_encode(\$freq_counts) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: '#6366f1',
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#6366f1',
                    borderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    r: {
                        angleLines: { color: 'rgba(0,0,0,0.05)' },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        pointLabels: {
                            font: { size: 12, family: \"'Inter', sans-serif\" },
                            color: '#64748b'
                        },
                        ticks: { display: false, backdropColor: 'transparent' }
                    }
                }
            }
        });
    }
";

// Reemplazar solo la parte del gráfico de frecuencia en el script existente
$content = preg_replace(
    '/\/\/ 3\. Frequency Chart \(Polar Area\) - NEW.*?}\s*}/s',
    $jsContent,
    $content
);

file_put_contents($file, $content);
echo "Updated to Radar Chart!\n";
?>