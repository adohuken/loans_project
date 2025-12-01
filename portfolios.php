<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

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

    // Unassign clients first (set portfolio_id to NULL)
    $stmt_unassign = $pdo->prepare("UPDATE clients SET portfolio_id = NULL WHERE portfolio_id = ?");
    $stmt_unassign->execute([$id]);

    // Delete the portfolio
    $stmt = $pdo->prepare("DELETE FROM portfolios WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: portfolios.php");
    exit;
}

// Fetch Portfolios with Client Count
$portfolios = $pdo->query("
    SELECT p.*, COUNT(c.id) as client_count 
    FROM portfolios p 
    LEFT JOIN clients c ON p.id = c.portfolio_id 
    GROUP BY p.id 
    ORDER BY p.name ASC
")->fetchAll();

// Include enhanced header
require 'components/enhanced_header.php';
?>

<div class="container">
    <div class="grid grid-2-3">
        <div class="card">
            <h2><i class="fas fa-<?= $edit_portfolio ? 'edit' : 'plus' ?>"></i>
                <?= $edit_portfolio ? 'Editar Cartera' : 'Nueva Cartera' ?></h2>
            <?php if (isset($error)): ?>
                <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
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
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                        <a href="portfolios.php" class="btn btn-secondary"
                            style="flex: 1; justify-content: center; text-align: center;">Cancelar</a>
                        <a href="portfolios.php?delete=<?= $edit_portfolio['id'] ?>" class="btn"
                            style="background-color: #fee2e2; color: #dc2626; border: 1px solid #dc2626; flex: 1; justify-content: center; text-align: center;"
                            onclick="return confirm('¿Seguro que deseas eliminar esta cartera? Los clientes asignados quedarán sin cartera.')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
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
                                        <a href="portfolios.php?delete=<?= $portfolio['id'] ?>" class="btn"
                                            style="background-color: #fff; color: #dc2626; border: 1px solid #dc2626; padding: 0.25rem 0.75rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.25rem;"
                                            onclick="return confirm('¿Seguro que deseas eliminar esta cartera? Los clientes asignados quedarán sin cartera.')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
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