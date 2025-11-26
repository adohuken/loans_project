<?php
require 'auth.php';
require 'db.php';

// Access Control: Only superadmin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuario no encontrado");
}

// Fetch Portfolios for Dropdown
$stmt_portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC");
$portfolios = $stmt_portfolios->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <script>
        function togglePortfolio() {
            const role = document.getElementById('role').value;
            const portfolioGroup = document.getElementById('portfolio-group');
            if (role === 'cobrador') {
                portfolioGroup.style.display = 'block';
                document.getElementById('portfolio_id').required = true;
            } else {
                portfolioGroup.style.display = 'none';
                document.getElementById('portfolio_id').required = false;
            }
        }
        window.onload = function () {
            togglePortfolio();
        };
    </script>
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
                <a href="portfolios.php">Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php" class="active">Usuarios</a>
                <?php endif; ?>
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

                <div class="form-group">
                    <label>Rol</label>
                    <select name="role" id="role" required onchange="togglePortfolio()">
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="cobrador" <?= $user['role'] === 'cobrador' ? 'selected' : '' ?>>Cobrador</option>
                        <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Super
                            Administrador</option>
                    </select>
                </div>

                <div class="form-group" id="portfolio-group">
                    <label>Cartera Asignada</label>
                    <select name="portfolio_id" id="portfolio_id">
                        <option value="">-- Seleccionar Cartera --</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>" <?= $user['portfolio_id'] == $portfolio['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($portfolio['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b;">El cobrador solo verá clientes de esta cartera</small>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Actualizar Usuario</button>
                <a href="users.php" class="btn btn-secondary"
                    style="display: block; text-align: center; margin-top: 10px;">Cancelar</a>
            </form>
        </div>
    </div>
</body>

</html>
