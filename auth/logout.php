<?php
require_once __DIR__ . '/../config/connection.php';

session_unset();

// Destroy the session
session_destroy();

// Redirect to the index page
header("Location: " . BASE_URL . "/index.php");
exit(); // Ensure no further code is executed after redirection
?>
