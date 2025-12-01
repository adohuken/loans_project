<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

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

// Include enhanced header
require 'components/enhanced_header.php';
?>

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

<div class="container">
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