<?php
require_once 'config/conexion.php';
require_once 'clases/Prestamo.php';

$database = new Conexion();
$db = $database->obtenerConexion();
$prestamo_manager = new Prestamo($db);

$metricas = $prestamo_manager->obtenerMetricasDashboard();
$proximos_abonos = $prestamo_manager->obtenerProximosAbonos(5);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistema de Préstamos</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-bottom: 30px; }
        .menu a { color: white; text-decoration: none; margin: 0 20px; font-weight: bold; padding: 5px 10px; border-radius: 4px; transition: background-color 0.3s; }
        .menu a:hover { background-color: #0056b3; }
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        
        /* Tarjetas de Métricas */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; color: #555; font-size: 16px; }
        .card .value { font-size: 28px; font-weight: bold; }
        
        /* Estilos de color para valores */
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }

        /* Lista de Pendientes */
        .pending-list h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-top: 0; }
        .pending-item { 
            background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; 
            margin-bottom: 10px; border-left: 5px solid #ffeeba; display: flex; justify-content: space-between; 
            align-items: center; font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Sistema de Préstamos</h1>
        <div class="menu">
            <a href="asignar_prestamo.php">Asignar Préstamo</a>
            <a href="realizar_abono.php">Registrar Abono</a>
        </div>
    </div>
    
    <div class="container">
        
        <div class="cards-grid">
            <div class="card">
                <h3>Total Clientes</h3>
                <div class="value"><?= $metricas['total_clientes'] ?></div>
            </div>
            
            <div class="card">
                <h3>Préstamos Activos</h3>
                <div class="value"><?= $metricas['prestamos_activos'] ?></div>
            </div>
            
            <div class="card">
                <h3>Monto Prestado Total</h3>
                <div class="value text-success">$<?= $metricas['monto_prestado_total'] ?></div>
            </div>
            
            <div class="card">
                <h3>Recaudado Hoy</h3>
                <div class="value text-success">$<?= $metricas['monto_abonado_hoy'] ?></div>
            </div>
        </div>
        
        <div class="pending-list">
            <h2>🔔 Próximos Pagos Pendientes (<?= count($proximos_abonos) ?>)</h2>
            <?php if (count($proximos_abonos) > 0): ?>
                <?php foreach ($proximos_abonos as $abono): ?>
                    <div class="pending-item">
                        <span><strong>Cliente:</strong> <?= htmlspecialchars($abono['nombre']) ?> (ID #<?= $abono['id_prestamo'] ?>)</span> 
                        <span><strong>Vence:</strong> <span class="text-danger"><?= $abono['fecha_vencimiento'] ?></span></span>
                        <span><strong>Monto:</strong> <span class="text-success">$<?= number_format($abono['monto_esperado'], 2) ?></span></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pending-item" style="background-color: #d4edda; color: #155724; justify-content: center;">
                    🎉 ¡No hay abonos pendientes cercanos!
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</body>
</html>