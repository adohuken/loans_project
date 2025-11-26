<?php
require 'auth.php';
require 'db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];

    // Prevent self-deletion
    if ($id_to_delete == $_SESSION['user_id']) {
        echo "<script>alert('No puedes eliminar tu propio usuario.'); window.location='users.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    header("Location: users.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="reports.php">Reportes</a>
                <a href="users.php" class="active">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Gestión de Usuarios</h2>
                <a href="create_user.php" class="btn">Nuevo Usuario</a>
            </div>

            <div class="table-responsive">
                <table style="margin-top: 1rem;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= $user['created_at'] ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>"
                                        class="btn btn-sm btn-secondary">Editar</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-secondary"
                                            style="background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca;"
                                            onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>