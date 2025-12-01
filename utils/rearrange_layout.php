<?php
$file = 'index.php';
$content = file_get_contents($file);

// Definir los bloques de código HTML
$incomeChartBlock = '
    <!-- Income Chart Section (Full Width) -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3><i class="fas fa-chart-bar"></i> Ingresos Mensuales (Últimos 12 Meses)</h3>
        <div style="max-height: 300px; position: relative;">
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

// Reemplazar la sección actual de gráficos y la sección de stats con el nuevo layout
// Buscamos desde "<!-- Charts Section -->" hasta el final del div de stats
$pattern = '/<!-- Charts Section -->.*?<div class="grid">.*?<\/div>\s*<\/div>/s';

// Construimos el nuevo contenido combinando los bloques
$newContent = $incomeChartBlock . "\n" . $gridBlock;

// Realizamos el reemplazo
$content = preg_replace($pattern, $newContent, $content);

file_put_contents($file, $content);
echo "Layout updated: Income Chart is now full width!\n";
?>