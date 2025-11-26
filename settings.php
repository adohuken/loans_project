<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $currency_symbol = $_POST['currency_symbol'];

    // Handle Logo Upload
    $logo_path = $_POST['current_logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, logo_path = ? WHERE id = 1");
    $stmt->execute([$company_name, $currency_symbol, $logo_path]);
    $message = "Configuración actualizada correctamente.";
}

// Fetch Current Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-cog"></i> Sistema de Préstamos</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Configuración</a>
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

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2><i class="fas fa-sliders-h"></i> Configuración del Sistema</h2>
            <?php if ($message): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Símbolo de Moneda</label>
                    <input type="text" name="currency_symbol"
                        value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Logo de la Empresa</label>
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Current Logo"
                                style="height: 50px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/*">
                    <input type="hidden" name="current_logo" value="<?= htmlspecialchars($settings['logo_path']) ?>">
                </div>

                <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Cambios</button>
            </form>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e2e8f0;">

                <div style="background: #fee2e2; padding: 1rem; border-radius: 8px; border: 1px solid #fecaca;">
                    <h3 style="color: #b91c1c; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Zona de
                        Peligro</h3>
                    <p style="color: #7f1d1d; margin-bottom: 1rem;">Estas acciones son irreversibles.</p>
                    <a href="reset_system.php" class="btn btn-secondary" style="color: #dc2626; border-color: #dc2626;"
                        onclick="return confirm('¿ESTÁS SEGURO? Esto borrará TODOS los préstamos, pagos y clientes. Solo quedarán los usuarios y la configuración.')">
                        <i class="fas fa-trash"></i> Reiniciar Sistema (Factory Reset)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>