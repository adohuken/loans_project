<?php
require 'db.php';

// Update SuperAdmin password to $uperAdmin2648!
$new_password = '$uperAdmin2648!';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'SuperAdmin'");
$result = $stmt->execute([$hash]);

if ($result) {
    echo "✅ Contraseña de SuperAdmin actualizada correctamente.\n";
    echo "Usuario: SuperAdmin\n";
    echo "Nueva contraseña: \$uperAdmin2648!\n";
} else {
    echo "❌ Error al actualizar la contraseña.\n";
}
?>