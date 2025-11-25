<?php
require 'auth.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Usuario - Sistema de Préstamos</title>
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
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h2>Crear Nuevo Usuario</h2>
            <form action="save_user.php" method="POST" style="margin-top: 1rem;">
                <div class="form-group">
                    <label>Nombre de Usuario</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Crear Usuario</button>
            </form>
        </div>
    </div>
</body>

</html>