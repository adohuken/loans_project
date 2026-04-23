<?php
require 'auth.php';
require 'db.php';

// Handle Deletion (Admins only)
if (isset($_GET['delete']) && $_SESSION['role'] === 'superadmin') {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM rent_receipts WHERE id = ?")->execute([$id]);
    header("Location: rent_receipts.php?msg=deleted");
    exit;
}

// Fetch all rent receipts
$receipts = $pdo->query("SELECT * FROM rent_receipts ORDER BY id DESC")->fetchAll();

// Settings for currency
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';

require 'components/enhanced_header.php';
?>

<style>
    .rent-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--bg-primary);
        border-radius: 12px;
        overflow: hidden;
    }

    .modern-table th {
        background: var(--bg-tertiary);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-primary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .modern-table tr:hover td {
        background: var(--bg-secondary);
    }

    .card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px var(--shadow);
    }

    .btn-rent-primary {
        background: var(--accent-primary);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }

    .btn-rent-primary:hover {
        background: var(--accent-secondary);
        transform: translateY(-2px);
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        transition: all 0.2s;
        text-decoration: none;
    }

    .action-btn:hover {
        background: var(--accent-primary);
        color: white;
        transform: scale(1.1);
    }

    .action-btn.delete:hover {
        background: var(--danger-color, #ef4444);
        color: white;
    }
</style>

<div class="rent-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);"><i class="fas fa-history" style="color: var(--accent-primary);"></i> Historial de Recibos de Renta</h2>
            <p style="color: var(--text-secondary); margin: 0.5rem 0 0 0;">Gestione y consulte los pagos de alquiler realizados.</p>
        </div>
        <a href="create_rent_receipt.php" class="btn-rent-primary">
            <i class="fas fa-plus-circle"></i> Nuevo Recibo
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fecaca;">
            Recibo eliminado correctamente.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha Pago</th>
                        <th>Inquilino</th>
                        <th>Monto</th>
                        <th>Concepto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                No hay recibos generados todavía.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($receipts as $r): ?>
                        <tr>
                            <td><span style="font-family: monospace; color: var(--text-secondary);">#<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($r['payment_date'])) ?></td>
                            <td><strong><?= htmlspecialchars($r['tenant_name']) ?></strong></td>
                            <td><?= $currency . number_format($r['amount'], 2) ?></td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($r['concept']) ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="view_rent_receipt.php?id=<?= $r['id'] ?>" class="action-btn" title="Ver / Imprimir">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                                        <a href="#" onclick="confirmDelete(<?= $r['id'] ?>)" class="action-btn delete" style="color: var(--danger-color);" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'rent_receipts.php?delete=' + id;
        }
    })
}
</script>
</body>
</html>
