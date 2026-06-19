<?php
require_once 'config/conexion.php';
require_once 'clases/Prestamo.php';

$database = new Conexion();
$db = $database->obtenerConexion();
$prestamo_manager = new Prestamo($db);

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datos_cliente = [
        'nombre' => trim($_POST['nombre']),
        'identificacion' => trim($_POST['identificacion']),
        'telefono' => trim($_POST['telefono']),
        'direccion' => trim($_POST['direccion'])
    ];

    $resultado = $prestamo_manager->registrarCliente($datos_cliente);

    if ($resultado === true) {
        $mensaje = "✅ Cliente '" . htmlspecialchars($datos_cliente['nombre']) . "' registrado con éxito.";
    } elseif (is_string($resultado)) {
        // Manejo de errores específicos (como el de identificación duplicada)
        $mensaje = "❌ " . $resultado;
    } else {
        $mensaje = "❌ Error desconocido al registrar el cliente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Clientes</title>
    <style>
        /* Usar estilos similares a los otros módulos para mantener la consistencia */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 4px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 16px; width: 100%; }
        button:hover { background-color: #1e7e34; }
        .mensaje { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .menu-link { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>👤 Registro de Nuevo Cliente</h2>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="registrar_cliente.php">
            
            <label for="nombre">Nombre Completo:</label>
            <input type="text" name="nombre" required>

            <label for="identificacion">Identificación (Cédula/DNI):</label>
            <input type="text" name="identificacion" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono">

            <label for="direccion">Dirección:</label>
            <input type="text" name="direccion">

            <button type="submit">Guardar Cliente</button>
        </form>
        
        <a href="index.php" class="menu-link">← Volver al Dashboard</a>
    </div>
</body>
</html>