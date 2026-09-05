<?php
/**
 * Database Schema Installation Script
 * Run this file once to set up the database tables and sample data
 */

require_once __DIR__ . '/config/connection.php';

echo "<h2>Installing Database Schema...</h2>";

$schemaFile = __DIR__ . '/database_schema.sql';

if (!file_exists($schemaFile)) {
    die("<p style='color:red'>Schema file not found: $schemaFile</p>");
}

$sql = file_get_contents($schemaFile);

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $stmt) {
    if (empty($stmt) || str_starts_with($stmt, '--')) continue;
    
    if ($conn->query($stmt)) {
        $success++;
    } else {
        $errors++;
        echo "<p style='color:red'>Error: " . $conn->error . "</p>";
        echo "<p>Statement: " . htmlspecialchars(substr($stmt, 0, 200)) . "...</p>";
    }
}

echo "<h3>Installation Complete</h3>";
echo "<p>Successful queries: $success</p>";
echo "<p>Errors: $errors</p>";

if ($errors === 0) {
    echo "<p style='color:green'><strong>Database schema installed successfully!</strong></p>";
    echo "<p>You can now access the website.</p>";
    echo "<p><a href='/Ecommerce%20website/index.php'>Go to Homepage</a></p>";
} else {
    echo "<p style='color:orange'>Some queries failed. Check the errors above.</p>";
}

$conn->close();