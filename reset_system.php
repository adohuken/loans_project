$pdo->rollBack();
}

// Disable foreign key checks temporarily
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// Start transaction
$pdo->beginTransaction();

// Delete all payments
$pdo->exec("DELETE FROM payments");

// Delete all loans
$pdo->exec("DELETE FROM loans");

// Delete all clients
$pdo->exec("DELETE FROM clients");

// Reset settings to defaults (keep only structure)
$pdo->exec("UPDATE settings SET
company_name = 'Mi Empresa',
currency_symbol = '$',
logo_path = '',
company_address = '',
company_phone = '',
receipt_footer = ''
WHERE id = 1
");

// Keep only admin user (id = 1), delete others
$pdo->exec("DELETE FROM users WHERE id != 1");

// Reset admin password to 'admin'
$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = 1");
$stmt->execute([$hash]);

// Reset auto-increment counters
$pdo->exec("ALTER TABLE clients AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE loans AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE payments AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE users AUTO_INCREMENT = 2");

// Commit transaction
$pdo->commit();

// Re-enable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

$success = true;

} catch (Exception $e) {
// Rollback if in transaction
if ($pdo->inTransaction()) {
$pdo->rollBack();
}
// Re-enable foreign key checks
try {
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
} catch (Exception $e2) {
// Ignore
}
$error = $e->getMessage();
}
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiniciar Sistema - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h2 style="color: #dc2626;">⚠️ Reiniciar Sistema</h2>

            <?php if (isset($success)): ?>
                <div
                    style="background: #dcfce7; border: 1px solid #86efac; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3 style="color: #166534; margin: 0 0 0.5rem 0;">✅ Sistema Reiniciado Exitosamente</h3>
                    <p style="margin: 0; color: #166534;">Todos los datos han sido eliminados. El sistema está listo para
                        una nueva empresa.</p>
                    <p style="margin: 0.5rem 0 0 0; color: #166534;"><strong>Usuario:</strong> admin |
                        <strong>Contraseña:</strong> admin</p>
                </div>
                <a href="settings.php" class="btn">Volver a Configuración</a>
                <a href="logout.php" class="btn btn-secondary" style="margin-left: 10px;">Cerrar Sesión</a>
            <?php elseif (isset($error)): ?>
                <div
                    style="background: #fee2e2; border: 1px solid #fecaca; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3 style="color: #dc2626; margin: 0 0 0.5rem 0;">❌ Error</h3>
                    <p style="margin: 0; color: #dc2626;"><?= htmlspecialchars($error) ?></p>
                </div>
                <a href="settings.php" class="btn">Volver a Configuración</a>
            <?php else: ?>
                <div
                    style="background: #fef9c3; border: 1px solid #fde68a; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h3 style="color: #854d0e; margin: 0 0 0.5rem 0;">⚠️ ADVERTENCIA</h3>
                    <p style="margin: 0; color: #854d0e;">Esta acción es <strong>IRREVERSIBLE</strong> y eliminará
                        permanentemente:</p>
                </div>

                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0;">Se Eliminará:</h3>
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <li>✗ Todos los <strong>Clientes</strong></li>
                        <li>✗ Todos los <strong>Préstamos</strong></li>
                        <li>✗ Todos los <strong>Pagos</strong> y calendarios</li>
                        <li>✗ Todos los <strong>Usuarios</strong> (excepto admin)</li>
                        <li>✗ Toda la <strong>Configuración</strong> de la empresa (logo, nombre, etc.)</li>
                    </ul>

                    <h3 style="margin-top: 1.5rem;">Se Mantendrá:</h3>
                    <ul style="margin: 0; padding-left: 1.5rem; color: #10b981;">
                        <li>✓ Usuario <strong>admin</strong> (contraseña: admin)</li>
                        <li>✓ Estructura de la base de datos</li>
                    </ul>
                </div>

                <form method="POST"
                    onsubmit="return confirm('¿ESTÁS COMPLETAMENTE SEGURO? Esta acción NO se puede deshacer.');">
                    <div class="form-group">
                        <label style="color: #dc2626; font-weight: bold;">Para confirmar, escribe: REINICIAR</label>
                        <input type="text" name="confirm" required placeholder="Escribe REINICIAR en mayúsculas"
                            style="border: 2px solid #dc2626;">
                    </div>

                    <button type="submit" class="btn" style="background: #dc2626; width: 100%;">
                        🗑️ REINICIAR SISTEMA COMPLETO
                    </button>
                </form>

                <a href="settings.php" class="btn btn-secondary" style="width: 100%; margin-top: 1rem; text-align: center;">
                    Cancelar y Volver
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>