<?php
// Script to add username display to all pages

$files_to_update = [
    'clients.php',
    'active_loans.php',
    'users.php',
    'settings.php',
    'backup.php',
    'create_loan.php',
    'create_user.php',
    'edit_user.php',
    'client_history.php',
    'loan_details.php',
    'import_loan.php',
    'reset_system.php',
    'reports.php'
];

$search = '<a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>';
$replace = '<span style="color: #fff; opacity: 0.8; margin-left: auto;">👤 <?= htmlspecialchars($_SESSION[\'username\']) ?></span>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>';

$updated = 0;
$errors = 0;

foreach ($files_to_update as $file) {
    $filepath = __DIR__ . '/' . $file;

    if (!file_exists($filepath)) {
        echo "⚠️  File not found: $file\n";
        $errors++;
        continue;
    }

    $content = file_get_contents($filepath);
    $original_content = $content;

    // Replace only the first occurrence (main navigation)
    $content = preg_replace(
        '/' . preg_quote($search, '/') . '/',
        $replace,
        $content,
        1
    );

    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        echo "✅ Updated: $file\n";
        $updated++;
    } else {
        echo "ℹ️  No changes needed: $file\n";
    }
}

echo "\n📊 Summary:\n";
echo "   Updated: $updated files\n";
echo "   Errors: $errors files\n";
echo "   Total: " . count($files_to_update) . " files\n";
?>