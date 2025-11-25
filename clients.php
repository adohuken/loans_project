<?php
require 'auth.php';
require 'db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: clients.php");
    exit;
}

// Fetch Clients
$stmt = $pdo->query("SELECT * FROM clients ORDER BY id DESC");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php" class="active">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="grid">
            <div class="card">
                <h2>Registrar Cliente</h2>
                <form action="save_client.php" method="POST" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label>Cédula / Identificación</label>
                        <input type="text" name="cedula" required placeholder="Ej. 123456789">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="name" required placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" placeholder="Ej. 555-1234">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="address" placeholder="Ej. Av. Principal #123">
                    </div>
                    <button type="submit" class="btn">Guardar Cliente</button>
                </form>
            </div>

            <div class="card" style="grid-column: span 2;">
                <h2>Lista de Clientes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
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
                                <td><?= htmlspecialchars($client['phone']) ?></td>
                                <td><?= htmlspecialchars($client['address']) ?></td>
                                <td>
                                    <a href="clients.php?delete=<?= $client['id'] ?>" class="btn btn-sm btn-secondary"
                                        onclick="return confirm('¿Seguro?')">Eliminar</a>
                                    <a href="client_history.php?id=<?= $client['id'] ?>" class="btn btn-sm"
                                        style="background-color: #3b82f6;">Historial</a>
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