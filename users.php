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

<style>
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 2rem;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .card h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }

    .btn-secondary:hover {
        background: var(--secondary-surface);
        color: var(--text-primary);
    }

    .btn-danger {
        background: #fef2f2;
        color: var(--danger-color);
        border: 1px solid #fee2e2;
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }

    .btn-danger:hover {
        background: #fee2e2;
    }

    /* Table */
    .table-responsive {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .modern-table tr:hover td {
        background: var(--secondary-surface);
    }

    .badge-role {
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-superadmin {
        background: #ede9fe;
        color: #7c3aed;
    }

    .badge-admin {
        background: #dbeafe;
        color: #2563eb;
    }

    .badge-cobrador {
        background: #d1fae5;
        color: #059669;
    }

    .badge-portfolio {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
</style>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users-cog" style="color: var(--primary-color);"></i> Usuarios del Sistema</h2>
            <a href="create_user.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
        </div>

        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Cartera Asignada</th>
                        <th>Creado</th>
                        <th style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><span
                                    style="font-family: monospace; color: var(--text-secondary);">#<?= $user['id'] ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div
                                        style="width: 32px; height: 32px; background: var(--secondary-surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'superadmin'): ?>
                                    <span class="badge-role badge-superadmin">Super Admin</span>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <span class="badge-role badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge-role badge-cobrador">Cobrador</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'cobrador'): ?>
                                    <?php if ($user['portfolio_name']): ?>
                                        <span class="badge-portfolio">
                                            <i class="fas fa-folder" style="color: #94a3b8;"></i>
                                            <?= htmlspecialchars($user['portfolio_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--danger-color); font-size: 0.85rem; font-weight: 500;">
                                            <i class="fas fa-exclamation-circle"></i> Sin Asignar
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-danger"
                                            onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
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