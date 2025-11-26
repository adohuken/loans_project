<?php
require 'auth.php';
require 'db.php';

$client_id = $_GET['id'];

// Fetch Client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client)
    die("Cliente no encontrado");

// Fetch Loans
$stmt_loans = $pdo->prepare("SELECT * FROM loans WHERE client_id = ? ORDER BY id DESC");
$stmt_loans->execute([$client_id]);
$loans = $stmt_loans->fetchAll();

// Stats
$total_loans = count($loans);
$active_loans = 0;
$paid_loans = 0;
foreach ($loans as $l) {
    if ($l['status'] == 'active')
        $active_loans++;
    else
        $paid_loans++;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Crediticio - <?= htmlspecialchars($client['name']) ?></title>
    <link rel="stylesheet" href="style.css?v=2.0">
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
                <a href="reports.php">Reportes</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card">
            <h2>Historial Crediticio: <?= htmlspecialchars($client['name']) ?></h2>
            <p><strong>Cédula:</strong> <?= htmlspecialchars($client['cedula'] ?? 'N/A') ?></p>

            <div class="grid" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                <div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; box-shadow: none;">
                    <h3>Total Préstamos</h3>
                    <p style="font-size: 1.5rem; font-weight: bold;"><?= $total_loans ?></p>
                </div>
                <div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0; box-shadow: none;">
                    <h3>Pagados</h3>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #166534;"><?= $paid_loans ?></p>
                </div>
                <div class="card" style="background: #fffbeb; border: 1px solid #fde68a; box-shadow: none;">
</body>

</html>