<?php
require 'auth.php';
require 'db.php';

// Only SuperAdmin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('No puedes eliminar tu propio usuario.'); window.location.href='users.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: users.php");
    exit;
}

// Fetch Users with Portfolio Name
$users = $pdo->query("
    SELECT u.*, p.name as portfolio_name 
    FROM users u 
    LEFT JOIN portfolios p ON u.portfolio_id = p.id 
    ORDER BY u.id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-user-shield"></i> Sistema de Préstamos</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <a href="users.php" class="active"><i class="fas fa-user-shield"></i> Usuarios</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </header>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-users-cog"></i> Usuarios del Sistema</h2>
                <a href="create_user.php" class="btn"><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Cartera Asignada</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td>
                                    <?php if ($user['role'] === 'superadmin'): ?>
                                        <span class="badge" style="background: #7c3aed; color: white;">Super Admin</span>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                        <span class="badge" style="background: #2563eb; color: white;">Admin</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #059669; color: white;">Cobrador</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'cobrador'): ?>
                                        <?php if ($user['portfolio_name']): ?>
                                            <span class="badge" style="background: #e0e7ff; color: #4338ca;">
                                                <i class="fas fa-folder"></i> <?= htmlspecialchars($user['portfolio_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Sin
                                                Asignar</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $user['created_at'] ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary"><i
                                            class="fas fa-edit"></i> Editar</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-secondary"
                                            style="color: #dc2626; border-color: #dc2626;"
                                            onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
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