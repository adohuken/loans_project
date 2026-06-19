<?php
require_once 'config/conexion.php';
require_once 'clases/Prestamo.php';

$database = new Conexion();
$db = $database->obtenerConexion();
$prestamo_manager = new Prestamo($db);

$mensaje = "";
$prestamos_activos = $prestamo_manager->obtenerPrestamosActivos();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_prestamo = (int)$_POST['id_prestamo'];
    $monto_pagado = (float)$_POST['monto_pagado'];
    
    if ($monto_pagado <= 0) {
        $mensaje = "❌ El monto pagado debe ser mayor a cero.";
    } else {
        $datos_recibo = $prestamo_manager->registrarAbono($id_prestamo, $monto_pagado);

        if ($datos_recibo) {
            // Redireccionar al script que genera el PDF
            header("Location: generar_recibo.php?id_abono=" . $datos_recibo['id_abono']);
            exit;
        } else {
            $mensaje = "❌ Error al registrar el abono o no hay pagos pendientes para ese préstamo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Abono</title>
    <style>
        /* Usar estilos similares a asignar_prestamo.php */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 4px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 16px; width: 100%; }
        button:hover { background-color: #0056b3; }
        .mensaje { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrar Abono</h2>
        <?php if ($mensaje): ?>
            <div class="mensaje" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <form method="POST" action="realizar_abono.php">
            <label for="id_prestamo">Seleccionar Préstamo (Cliente):</label>
            <select name="id_prestamo" required>
                <?php if (count($prestamos_activos) > 0): ?>
                    <?php foreach ($prestamos_activos as $p): ?>
                        <option value="<?= $p['id_prestamo'] ?>"><?= htmlspecialchars($p['nombre']) ?> (Préstamo #<?= $p['id_prestamo'] ?>)</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>-- No hay préstamos activos --</option>
                <?php endif; ?>
            </select>

            <label for="monto_pagado">Monto Abonado ($):</label>
            <input type="number" step="0.01" name="monto_pagado" required>

            <button type="submit">Registrar Abono y Generar Recibo</button>
        </form>
    </div>
</body>
</html>