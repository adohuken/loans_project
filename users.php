<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

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

// Include enhanced header
require 'components/enhanced_header.php';
?>

<div class="container">
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