<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_name = $_POST['tenant_name'] ?? '';
    $tenant_id_number = $_POST['tenant_id_number'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $concept = $_POST['concept'] ?? '';

    if (!empty($tenant_name) && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO rent_receipts (tenant_name, tenant_id_number, amount, payment_date, concept) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_name, $tenant_id_number, $amount, $payment_date, $concept]);
        $receipt_id = $pdo->lastInsertId();
        header("Location: view_rent_receipt.php?id=" . $receipt_id);
        exit;
    }
}

require 'components/enhanced_header.php';
?>

<style>
    .rent-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
    }

    .card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px var(--shadow);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 1rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: var(--accent-primary);
        outline: none;
        box-shadow: 0 0 0 3px var(--accent-light);
    }

    .btn-rent-primary {
        background: var(--accent-primary);
        color: white;
        padding: 0.875rem 2rem;
        border-radius: 10px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        justify-content: center;
    }

    .btn-rent-primary:hover {
        background: var(--accent-secondary);
        transform: translateY(-2px);
    }

    .btn-rent-secondary {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        padding: 0.875rem 2rem;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        justify-content: center;
    }
</style>

<div class="rent-container">
    <div class="card">
        <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;"><i class="fas fa-plus-circle" style="color: var(--accent-primary);"></i> Crear Recibo de Renta</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Ingrese los datos para generar un nuevo comprobante de pago de alquiler.</p>
        
        <form method="POST">
            <div class="form-group">
                <label>Nombre del Inquilino:</label>
                <input type="text" name="tenant_name" class="form-control" required placeholder="Nombre completo">
            </div>
            
            <div class="form-group">
                <label>Identificación (Cédula/DNI):</label>
                <input type="text" name="tenant_id_number" class="form-control" placeholder="Número de identificación">
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label>Monto:</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Fecha de Pago:</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Concepto:</label>
                <textarea name="concept" class="form-control" placeholder="Ej: Pago de renta mes de Abril 2024" style="min-height: 120px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn-rent-primary" style="flex: 2;">
                    <i class="fas fa-save"></i> Generar y Ver Recibo
                </button>
                <a href="rent_receipts.php" class="btn-rent-secondary" style="flex: 1;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
