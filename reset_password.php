<?php
// ⚠️ SCRIPT TEMPORAL - ELIMINAR DESPUÉS DE USARLO ⚠️

require_once 'db.php';

$nueva_password = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_password = trim($_POST['password'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');

    if (empty($nueva_password)) {
        $mensaje = ['tipo' => 'error', 'texto' => 'La contraseña no puede estar vacía.'];
    } elseif ($nueva_password !== $confirmar) {
        $mensaje = ['tipo' => 'error', 'texto' => 'Las contraseñas no coinciden.'];
    } else {
        $hash = password_hash($nueva_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE role = 'superadmin' LIMIT 1");
        $stmt->execute([$hash]);

        if ($stmt->rowCount() > 0) {
            $mensaje = ['tipo' => 'ok', 'texto' => '✅ Contraseña actualizada correctamente. ¡Elimina este archivo del servidor!'];
        } else {
            $mensaje = ['tipo' => 'error', 'texto' => 'No se encontró ningún usuario con rol superadmin.'];
        }
    }
}

// Obtener usuario superadmin actual
$stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'superadmin' LIMIT 1");
$superadmin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reset Contraseña SuperAdmin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 450px;
            margin: 60px auto;
            background: #f4f4f4;
        }

        .card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-top: 0;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type=password] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #e53e3e;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background: #c53030;
        }

        .ok {
            background: #c6f6d5;
            color: #276749;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .info {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .warning {
            background: #fefcbf;
            color: #744210;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>🔑 Reset Contraseña SuperAdmin</h2>

        <?php if ($superadmin): ?>
            <div class="info">Usuario encontrado: <strong>
                    <?= htmlspecialchars($superadmin['username']) ?>
                </strong> (ID:
                <?= $superadmin['id'] ?>)
            </div>
        <?php else: ?>
            <div class="error">⚠️ No se encontró ningún usuario con rol <strong>superadmin</strong> en la base de datos.
            </div>
        <?php endif; ?>

        <?php if ($mensaje): ?>
            <div class="<?= $mensaje['tipo'] ?>">
                <?= $mensaje['texto'] ?>
            </div>
        <?php endif; ?>

        <?php if ($superadmin): ?>
            <form method="POST">
                <label>Nueva contraseña:</label>
                <input type="password" name="password" required minlength="6">
                <label>Confirmar contraseña:</label>
                <input type="password" name="confirmar" required minlength="6">
                <button type="submit">Cambiar Contraseña</button>
            </form>
        <?php endif; ?>

        <div class="warning">⚠️ <strong>IMPORTANTE:</strong> Elimina este archivo del servidor inmediatamente después de
            usarlo.</div>
    </div>
</body>

</html>