<?php
// database_installer.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$server   = "localhost";
$username = "root";
$password = "";
$dbname   = "ecommerce";

echo "Connecting to MySQL...\n";
$conn = new mysqli($server, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected.\n";

// Drop & recreate database
$conn->query("DROP DATABASE IF EXISTS `$dbname`");
if ($conn->query("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "Database '$dbname' created.\n";
} else {
    die("Create DB failed: " . $conn->error . "\n");
}
$conn->select_db($dbname);

// Load schema
$sqlFile = __DIR__ . '/database_schema.sql';
if (!file_exists($sqlFile)) die("Schema file not found: $sqlFile\n");
$sql = file_get_contents($sqlFile);

echo "Importing schema via multi_query...\n";
if ($conn->multi_query($sql)) {
    $count = 0;
    do {
        $count++;
        // flush result set
        while ($conn->more_results() && $conn->next_result()) {
            $extra = $conn->store_result();
            if ($extra) $extra->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Executed batch. Statements processed: $count\n";
} else {
    echo "multi_query error: " . $conn->error . "\n";
}

// Report tables
$res = $conn->query("SHOW TABLES");
echo "\nTables in '$dbname':\n";
if ($res) {
    while ($row = $res->fetch_row()) {
        echo " - " . $row[0] . "\n";
    }
} else {
    echo "Error listing tables: " . $conn->error . "\n";
}

$conn->close();
echo "\nDone.\n";
?>