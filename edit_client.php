<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: clients.php");
    exit;
}

// Fetch Client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    die("Cliente no encontrado");
}

// Fetch Portfolios for Dropdown
$portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();

// Include enhanced header
require 'components/enhanced_header.php';
?>

<div class="container">
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2><i class="fas fa-user-edit"></i> Editar Cliente</h2>
            <a href="clients.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <form action="update_client.php" method="POST">
            <input type="hidden" name="id" value="<?= $client['id'] ?>">

            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Cédula / DNI</label>
                <input type="text" name="cedula" value="<?= htmlspecialchars($client['cedula']) ?>">
            </div>

            <div class="form-group">
                <label>Cartera</label>
                <select name="portfolio_id"
                    style="width: 100%; padding: 0.875rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white;">
                    <option value="">-- Seleccionar Cartera --</option>
                    <?php foreach ($portfolios as $portfolio): ?>
                        <option value="<?= $portfolio['id'] ?>" <?= $client['portfolio_id'] == $portfolio['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($portfolio['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #64748b; margin-top: 0.5rem; display: block;">
                    <i class="fas fa-info-circle"></i> Asigne una cartera si el cliente aún no tiene una.
                </small>
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($client['phone']) ?>">
            </div>

            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="address" value="<?= htmlspecialchars($client['address']) ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fas fa-save"></i> Actualizar Cliente
            </button>
        </form>
    </div>
</div>
</body>

</html>