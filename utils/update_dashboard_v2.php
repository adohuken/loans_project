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

// 3. Mejorar layout de gráficos (Full Width Income Chart)
$incomeChartBlock = '
    <!-- Income Chart Section (Full Width) -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3><i class="fas fa-chart-bar"></i> Ingresos Mensuales (Últimos 12 Meses)</h3>
        <div style="max-height: 300px; position: relative; width: 100%;">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>';

$gridBlock = '
    <!-- Stats & Pie Chart Grid -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        <div class="card">
            <h3><i class="fas fa-chart-pie"></i> Estado de Préstamos</h3>
            <div style="max-height: 200px; position: relative;">
                <canvas id="loanStatusChart"></canvas>
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
    </div>';

// Reemplazar la sección actual de gráficos y la sección de stats
$pattern = '/<!-- Charts Section -->.*?<div class="grid">.*?<\/div>\s*<\/div>/s';
$newContent = $incomeChartBlock . "\n" . $gridBlock;
$content = preg_replace($pattern, $newContent, $content);

// 4. Reemplazar script de gráficos con maintainAspectRatio: false
$newScript = file_get_contents('utils/enhanced_charts.txt');
if (preg_match('/<script>(.*?)<\/script>/s', $newScript, $matches)) {
    $jsContent = $matches[1];

    // Inyectar maintainAspectRatio: false en el gráfico de ingresos
    $jsContent = str_replace(
        "options: {\n                responsive: true,\n                maintainAspectRatio: true,",
        "options: {\n                responsive: true,\n                maintainAspectRatio: false,",
        $jsContent
    );

    // Reemplazar el script anterior
    $content = preg_replace(
        '/<script>\s*\/\/ Loan Status Chart \(Pie\).*?<\/script>/s',
        "<script>\n" . $jsContent . "\n</script>",
        $content
    );
}

file_put_contents($file, $content);
echo "Dashboard updated successfully with responsive charts!\n";
?>