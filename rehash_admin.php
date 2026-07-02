<?php
/**
 * ONE-TIME USE: Run this once in your browser (e.g. /rehash_admin.php)
 * to convert the seeded admin's plaintext password ('admin') into a
 * proper bcrypt hash. Delete this file afterwards.
 */
require_once __DIR__ . '/includes/config.php';

$email = 'admin@gmail.com';
$plain = 'admin';

$stmt = mysqli_prepare($conn, "SELECT id, password FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    die('Admin user not found.');
}

// Only rehash if it isn't already a bcrypt hash
if (password_get_info($row['password'])['algo'] === null) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, 'si', $hash, $row['id']);
    mysqli_stmt_execute($upd);
    echo "Admin password hashed successfully. Delete this file now.";
} else {
    echo "Admin password is already hashed. Nothing to do.";
}