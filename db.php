<?php
// db.php - Auto-detect environment (Local vs Production)

$host_env = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = ($host_env === 'localhost' || strpos($host_env, '127.0.0.1') !== false || php_sapi_name() === 'cli');

if ($is_local) {
    // Configuración LOCAL (XAMPP)
    $host = 'localhost';
    $dbname = 'loans_db';
    $username = 'root';
    $password = '';
} else {
    // Configuración PRODUCCIÓN (InfinityFree)
    $host = 'sql302.infinityfree.com'; // Por favor verifica si este es tu host de producción
    $dbname = 'if0_40835015_loans_db';
    $username = 'if0_40835015';
    $password = 'TU_PASSWORD_DE_PRODUCCION'; // REEMPLAZAR con tu contraseña real de InfinityFree
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Ajustar zona horaria (opcional)
    date_default_timezone_set('America/Mexico_City');
    try {
        $pdo->exec("SET time_zone = '-06:00'");
    } catch (PDOException $e) {
        // Ignorar si falla en hosting compartido
    }

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>