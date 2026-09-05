<?php
/**
 * Seed Admin User
 * -----------------
 * Run this script once to create the initial admin account.
 * Access it in the browser: http://localhost/Ecommerce%20website/seed_admin.php
 *
 * After running, you can delete this file or restrict access to it.
 */

$host = 'localhost';
$db = 'ecommerce';
$user = 'root';
$pass = '';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Admin credentials (change these before deploying)
$admin_first = 'Site';
$admin_last = 'Administrator';
$admin_email = 'admin@accessories.com';
$admin_username = 'admin';
$admin_phone = '+977 9800000000';
$admin_password = password_hash('Admin@123', PASSWORD_BCRYPT);
$admin_role = 'admin';

// Check if admin username or email already exists
$check = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
$stmt_check = mysqli_prepare($conn, $check);
mysqli_stmt_bind_param($stmt_check, "ss", $admin_username, $admin_email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    echo "<p style='font-family:sans-serif;color:#b91c1c;'>Admin user already exists. No changes made.</p>";
    mysqli_stmt_close($stmt_check);
    mysqli_close($conn);
    exit();
}
mysqli_stmt_close($stmt_check);

// Insert admin
$sql = "INSERT INTO users (first_name, last_name, email, username, password, phone, role)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssssss", $admin_first, $admin_last, $admin_email, $admin_username, $admin_password, $admin_phone, $admin_role);

if (mysqli_stmt_execute($stmt)) {
    echo "<p style='font-family:sans-serif;color:#15803d;'>Admin user created successfully.</p>";
    echo "<ul style='font-family:sans-serif;color:#334155;'>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> Admin@123</li>";
    echo "</ul>";
    echo "<p style='font-family:sans-serif;color:#64748b;'>Please change the password after first login, and delete this file.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#b91c1c;'>Failed to create admin: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
