<?php
// cleanup_data_dir.php - removes orphaned MySQL data directory for 'ecommerce'
$dataDir = 'C:\\xampp\\mysql\\data\\ecommerce';

if (!is_dir($dataDir)) {
    echo "Directory does not exist: $dataDir\n";
    exit;
}

echo "Removing orphaned files in: $dataDir\n";

$files = glob($dataDir . '\\*');
foreach ($files as $file) {
    if (is_file($file)) {
        if (unlink($file)) {
            echo "Deleted: " . basename($file) . "\n";
        } else {
            echo "FAILED to delete: " . basename($file) . "\n";
        }
    }
}

if (rmdir($dataDir)) {
    echo "Removed directory: $dataDir\n";
    echo "Done. You can now run database_installer.php\n";
} else {
    echo "Could not remove directory (still not empty?).\n";
    $remaining = glob($dataDir . '\\*');
    foreach ($remaining as $r) {
        echo "Remaining: $r\n";
    }
}
?>