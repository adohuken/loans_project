<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: clients.php");
    exit;
}

// Fetch Portfolios for Dropdown
$portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();

// Fetch Clients with Portfolio Name
$clients = $pdo->query("
    SELECT c.*, p.name as portfolio_name 
    FROM clients c 
    LEFT JOIN portfolios p ON c.portfolio_id = p.id 
    ORDER BY c.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Sistema de Préstamos</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php" class="active"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
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
                <h2><i class="fas fa-user-plus"></i> Nuevo Cliente</h2>
                <form action="save_client.php" method="POST">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Cédula / DNI</label>
                        <input type="text" name="cedula">
                    </div>
                    <div class="form-group">
                        <label>Cartera (Opcional)</label>
                        <select name="portfolio_id"
                            style="width: 100%; padding: 0.875rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white;">
                            <option value="">-- Seleccionar Cartera --</option>
                            <?php foreach ($portfolios as $portfolio): ?>
                                <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <textarea name="address"></textarea>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Cliente</button>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-list"></i> Lista de Clientes</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Cartera</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>#<?= $client['id'] ?></td>
                                    <td><?= htmlspecialchars($client['cedula'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['name']) ?></td>
                                    <td>
                                        <?php if ($client['portfolio_name']): ?>
                                            <span class="badge" style="background-color: #e0e7ff; color: #4338ca;">
                                                <i class="fas fa-folder"></i> <?= htmlspecialchars($client['portfolio_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Sin Asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td><?= htmlspecialchars($client['address']) ?></td>
                                    <td>
                                        <a href="clients.php?delete=<?= $client['id'] ?>" class="btn btn-sm btn-secondary"
                                            onclick="return confirm('¿Seguro?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="client_history.php?id=<?= $client['id'] ?>" class="btn btn-sm"
                                            style="background-color: #3b82f6;">
                                            <i class="fas fa-history"></i>
                                        </a>
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