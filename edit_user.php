<?php
require 'auth.php';
require 'db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuario no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php" class="active">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h2>Editar Usuario</h2>
            <form action="update_user.php" method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">

                <div class="form-group">
                    <label>Nombre de Usuario</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password" placeholder="Dejar en blanco para mantener la actual">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Actualizar Usuario</button>
                <a href="users.php" class="btn btn-secondary"
                    style="display: block; text-align: center; margin-top: 10px;">Cancelar</a>
            </form>
        </div>
    </div>
</body>

</html>