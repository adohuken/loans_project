<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Initialize edit_portfolio
$edit_portfolio = null;

// Handle Edit Portfolio Logic (Fetch data if edit param exists)
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM portfolios WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_portfolio = $stmt->fetch();
}

// Handle Edit Action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE portfolios SET name = ? WHERE id = ?");
        try {
            $stmt->execute([$name, $id]);
            header("Location: portfolios.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: El nombre de la cartera ya existe.";
        }
    }
}

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Handle Add Portfolio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO portfolios (name) VALUES (?)");
        try {
            $stmt->execute([$name]);
            header("Location: portfolios.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: El nombre de la cartera ya existe.";
        }
    }
}

// Handle Delete Portfolio
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if portfolio has clients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE portfolio_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $error = "No se puede eliminar la cartera porque tiene clientes asignados.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM portfolios WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: portfolios.php");
        exit;
    }
}

// Fetch Portfolios with Client Count
$portfolios = $pdo->query("
    SELECT p.*, COUNT(c.id) as client_count 
    FROM portfolios p 
    LEFT JOIN clients c ON p.id = c.portfolio_id 
    GROUP BY p.id 
    ORDER BY p.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carteras - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="mobile.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header style="flex-direction: column; gap: 1.5rem;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                        style="height: 60px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <h1 style="margin: 0; font-size: 2rem;"><?= htmlspecialchars($company_name) ?></h1>
            </div>
            <nav style="justify-content: center;">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php" class="active"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </header>

        <div class="grid grid-2-3">
            <div class="card">
                <h2><i class="fas fa-<?= $edit_portfolio ? 'edit' : 'plus' ?>"></i>
                    <?= $edit_portfolio ? 'Editar Cartera' : 'Nueva Cartera' ?></h2>
                <?php if (isset($error)): ?>
                    <div
                        style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $edit_portfolio ? 'edit' : 'add' ?>">
                    <?php if ($edit_portfolio): ?>
                        <input type="hidden" name="id" value="<?= $edit_portfolio['id'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Nombre de la Cartera</label>
                        <input type="text" name="name" required
                            value="<?= $edit_portfolio ? htmlspecialchars($edit_portfolio['name']) : '' ?>"
                            placeholder="Ej: Ruta Norte, Cobrador Juan...">
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-save"></i>
                        <?= $edit_portfolio ? 'Actualizar' : 'Guardar' ?> Cartera</button>
                    <?php if ($edit_portfolio): ?>
                        <a href="portfolios.php" class="btn btn-secondary"
                            style="margin-top: 0.5rem; display: inline-block; text-align: center; width: 100%; box-sizing: border-box;">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-list"></i> Carteras Existentes</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Clientes Asignados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($portfolios as $portfolio): ?>
                                <tr>
                                    <td>#<?= $portfolio['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($portfolio['name']) ?></strong></td>
                                    <td>
                                        <span class="badge" style="background-color: #e0e7ff; color: #4338ca;">
                                            <i class="fas fa-users"></i> <?= $portfolio['client_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="portfolios.php?edit=<?= $portfolio['id'] ?>" class="btn"
                                            style="background-color: #fff; color: #1e293b; border: 1px solid #cbd5e1; padding: 0.25rem 0.75rem; font-size: 0.85rem; margin-right: 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($portfolio['client_count'] == 0): ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>" class="btn"
                                                style="background-color: #fff; color: #dc2626; border: 1px solid #dc2626; padding: 0.25rem 0.75rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.25rem;"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 0.85rem;"><i class="fas fa-lock"></i> En
                                                uso</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>