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

<style>
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 2rem;
    }

    .card h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Forms */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-secondary);
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1.5rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        width: auto;
        /* Changed from 100% to auto */
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    .btn-secondary:hover {
        background: var(--secondary-surface);
        color: var(--text-primary);
    }

    .btn-block {
        width: 100%;
    }

    .btn-danger {
        background: #fef2f2;
        color: var(--danger-color);
        border: 1px solid #fee2e2;
    }

    .btn-danger:hover {
        background: #fee2e2;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 8px;
    }

    /* Alerts */
    .alert-error {
        background: #fef2f2;
        border: 1px solid #fee2e2;
        color: #991b1b;
        padding: 1rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .modern-table tr:hover td {
        background: var(--secondary-surface);
    }

    .badge-clients {
        background: #e0e7ff;
        color: #4338ca;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
</style>

<div class="container">
    <div class="main-grid">

        <!-- Form Column -->
        <div class="card">
            <h2>
                <i class="fas fa-<?= $edit_portfolio ? 'edit' : 'plus-circle' ?>"
                    style="color: var(--primary-color);"></i>
                <?= $edit_portfolio ? 'Editar Cartera' : 'Nueva Cartera' ?>
            </h2>

            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
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
                    <input type="text" name="name" class="form-control" required
                        value="<?= $edit_portfolio ? htmlspecialchars($edit_portfolio['name']) : '' ?>"
                        placeholder="Ej: Ruta Norte, Cobrador Juan...">
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i>
                        <?= $edit_portfolio ? 'Actualizar' : 'Guardar' ?> Cartera
                    </button>

                    <?php if ($edit_portfolio): ?>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="portfolios.php" class="btn btn-secondary" style="margin-top: 0;">Cancelar</a>

                            <a href="portfolios.php?delete=<?= $edit_portfolio['id'] ?>" class="btn btn-danger"
                                style="margin-top: 0;"
                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera? Los clientes asignados quedarán sin cartera.')">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- List Column -->
        <div class="card">
            <h2><i class="fas fa-list" style="color: var(--text-secondary);"></i> Carteras Existentes</h2>
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                            <th>Clientes Asignados</th>
                            <th style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <tr>
                                <td><span
                                        style="font-family: monospace; color: var(--text-secondary);">#<?= $portfolio['id'] ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($portfolio['name']) ?></strong></td>
                                <td>
                                    <span class="badge-clients">
                                        <i class="fas fa-users"></i> <?= $portfolio['client_count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="portfolios.php?edit=<?= $portfolio['id'] ?>"
                                            class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($portfolio['client_count'] == 0): ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera? Los clientes asignados quedarán sin cartera.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($portfolios)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #64748b;">
                                    <i class="fas fa-folder-open"
                                        style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No hay carteras creadas aún.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>

</html>