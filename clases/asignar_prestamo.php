<?php
require_once 'config/conexion.php';
require_once 'clases/Prestamo.php';

$database = new Conexion();
$db = $database->obtenerConexion();
$prestamo_manager = new Prestamo($db);

$mensaje = "";

// Simulación de obtener clientes (deberías tener una clase Cliente para esto)
$query_clientes = "SELECT id_cliente, nombre FROM clientes";
$stmt_clientes = $db->query($query_clientes);
$clientes_disponibles = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Cálculo de ejemplo para mostrar antes de enviar
$monto_ejemplo = 100; $tasa_ejemplo = 15; $plazo_ejemplo = 3;
$monto_total_ejemplo = $prestamo_manager->calcularMontoTotal($monto_ejemplo, $tasa_ejemplo, $plazo_ejemplo);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datos_prestamo = [
        'id_cliente' => $_POST['id_cliente'],
        'monto_inicial' => (float)$_POST['monto_inicial'],
        'tasa_interes' => (float)$_POST['tasa_interes'],
        'plazo_meses' => (int)$_POST['plazo_meses'],
        'frecuencia_pago' => $_POST['frecuencia_pago'],
        'fecha_inicio' => $_POST['fecha_inicio']
    ];

    if ($prestamo_manager->asignarPrestamo($datos_prestamo)) {
        $mensaje = "✅ Préstamo asignado y calendario de abonos generado con éxito.";
    } else {
        $mensaje = "❌ Error al asignar el préstamo. Verifique la conexión o los datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Nuevo Préstamo</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 4px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 16px; width: 100%; }
        button:hover { background-color: #1e7e34; }
        .mensaje { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Asignación de Préstamo</h2>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <p style="background-color:#eee; padding: 10px; border-radius: 4px;">
            **Fórmula de Interés Simple:** ($<?= $monto_ejemplo ?> inicial + 15% mensual * 3 meses). Total a pagar: **$<?= $monto_total_ejemplo ?>**
        </p>

        <form method="POST" action="asignar_prestamo.php">
            
            <label for="id_cliente">Cliente:</label>
            <select name="id_cliente" required>
                <?php if (count($clientes_disponibles) > 0): ?>
                    <?php foreach ($clientes_disponibles as $cliente): ?>
                        <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre']) ?> (ID: <?= $cliente['id_cliente'] ?>)</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>-- Registre un cliente primero --</option>
                <?php endif; ?>
            </select>

            <label for="monto_inicial">Monto Inicial ($):</label>
            <input type="number" step="0.01" name="monto_inicial" value="500.00" required>

            <label for="tasa_interes">Tasa de Interés Mensual (%):</label>
            <input type="number" step="0.01" name="tasa_interes" value="15.00" required>

            <label for="plazo_meses">Plazo (Meses):</label>
            <input type="number" name="plazo_meses" value="6" required>
            
            <label for="frecuencia_pago">Frecuencia de Abono:</label>
            <select name="frecuencia_pago" required>
                <option value="Mensual">Mensual</option>
                <option value="Quincenal">Quincenal</option>
                <option value="Semanal">Semanal</option>
                <option value="Diario">Diario</option>
            </select>

            <label for="fecha_inicio">Fecha de Inicio:</label>
            <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>

            <button type="submit">Asignar Préstamo y Generar Calendario</button>
        </form>
    </div>
</body>
</html>