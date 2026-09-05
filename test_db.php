<?php
require_once __DIR__ . '/config/connection.php';
echo "Testing products table...\n";
$res = $conn->query("SELECT COUNT(*) FROM products");
if ($res) {
    $count = $res->fetch_row()[0];
    echo "Products in table: $count\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nTesting categories table...\n";
$res = $conn->query("SELECT COUNT(*) FROM categories");
if ($res) {
    $count = $res->fetch_row()[0];
    echo "Categories in table: $count\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nTesting brands table...\n";
$res = $conn->query("SELECT COUNT(*) FROM brands");
if ($res) {
    $count = $res->fetch_row()[0];
    echo "Brands in table: $count\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>