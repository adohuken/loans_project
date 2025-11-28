<?php
require 'auth.php';
require 'db.php';

$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;
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
    <link rel="stylesheet" href="style.css?v=3.0">
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
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <?php endif; ?>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="clients.php" class="active"><i class="fas fa-users"></i> Clientes</a>
                <?php endif; ?>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                    <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                    <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <?php if ($user_role !== 'cobrador'): ?>
                    <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                        <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                    <?php endif; ?>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
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

