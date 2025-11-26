<?php
require 'auth.php';
require 'db.php';

// Fetch Active Loans
$stmt = $pdo->query("
    SELECT l.*, c.name, c.cedula 
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    WHERE l.status = 'active'
    ORDER BY l.id DESC
");
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonar (Créditos Activos) - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php" class="active">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="reports.php">Reportes</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card">
            <h2>Créditos Activos (Abonar)</h2>
            <p style="color: #64748b; margin-bottom: 1rem;">Selecciona un préstamo para registrar un pago.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Monto Total</th>
                            <th>Fecha Inicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>#<?= $loan['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($loan['name']) ?></strong><br>
                                    <small><?= htmlspecialchars($loan['cedula'] ?? '') ?></small>
                                </td>
                                <td>$<?= number_format($loan['total_amount'], 2) ?></td>
                                <td><?= $loan['start_date'] ?></td>
                                <td>
                                    <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-sm">Abonar /
                                        Ver
                                        Detalles</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($loans)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">No hay créditos
                                    activos en este
                                    momento.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>