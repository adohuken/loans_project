<?php
require 'auth.php';
require 'db.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-briefcase"></i> Sistema de Préstamos</h1>
            <nav>
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
                <h2><i class="fas fa-plus"></i> Nueva Cartera</h2>
                <?php if (isset($error)): ?>
                    <div
                        style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Nombre de la Cartera</label>
                        <input type="text" name="name" required placeholder="Ej: Ruta Norte, Cobrador Juan...">
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Cartera</button>
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
                                        <?php if ($portfolio['client_count'] == 0): ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>"
                                                class="btn btn-sm btn-secondary"
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