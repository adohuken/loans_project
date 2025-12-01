<?php
$file = 'index.php';
$content = file_get_contents($file);

// 1. Traducir estados
$content = str_replace(
    "<?= strtoupper(\$loan['status']) ?>",
    "<?= \$loan['status'] == 'active' ? 'ACTIVO' : 'PAGADO' ?>",
    $content
);

// 2. Cambiar intervalo a 12 meses
$content = str_replace("INTERVAL 6 MONTH", "INTERVAL 12 MONTH", $content);
$content = str_replace("\$i = 5;", "\$i = 11;", $content);

// 3. Mejorar layout de gráficos
$chartsSection = '    <!-- Charts Section -->
    <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 1.5rem;">
        <div class="card">
            <h3><i class="fas fa-chart-pie"></i> Estado de Préstamos</h3>
            <div style="max-height: 250px; position: relative;">
                <canvas id="loanStatusChart"></canvas>
            </div>
        </div>
        <div class="card">
            <h3><i class="fas fa-chart-bar"></i> Ingresos Mensuales (Últimos 12 Meses)</h3>
            <div style="max-height: 250px; position: relative;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
    </div>';

$content = preg_replace(
    '/<!-- Charts Section -->\s*<div class="grid grid-2-3">.*?<\/div>\s*<\/div>/s',
    $chartsSection,
    $content
);

// 4. Reemplazar script de gráficos
$newScript = file_get_contents('utils/enhanced_charts.txt');
// Extraer solo el contenido del script
if (preg_match('/<script>(.*?)<\/script>/s', $newScript, $matches)) {
    $jsContent = $matches[1];
    // Reemplazar el script anterior (buscando un patrón que coincida con el script original)
    $content = preg_replace(
        '/<script>\s*\/\/ Loan Status Chart \(Pie\).*?<\/script>/s',
        "<script>\n" . $jsContent . "\n</script>",
        $content
    );
}

file_put_contents($file, $content);
echo "Dashboard updated successfully!\n";
?>