<?php
$search = "strtoupper(\$loan['status'])";
$replace = "\$loan['status'] == 'active' ? 'ACTIVO' : 'PAGADO'";

$files = [
    'index.php',
    'active_loans.php',
    'loan_details.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace($search, $replace, $content);
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

echo "Done!\n";
?>