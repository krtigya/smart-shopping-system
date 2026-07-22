<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "ecommerce";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Base URL of the application (document-root relative). Used for links/redirects
// so they resolve correctly from any folder depth.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Ecommerce%20website');
}

// Filesystem path to the uploaded product images folder.
if (!defined('IMAGES_DIR')) {
    define('IMAGES_DIR', __DIR__ . '/../assets/images');
}
?>