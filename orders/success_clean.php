<?php
require_once __DIR__ . '/../config/connection.php'; // Ensure this file has your database connection

// Fetch transaction details from the session
$oid = $_SESSION['oid'] ?? '';
$amt = $_SESSION['amt'] ?? '';
$refId = $_SESSION['refId'] ?? '';
$userId = $_SESSION['user_id'] ?? ''; // Assuming you have user_id in session
$productId = $_SESSION['product_id'] ?? ''; // Assuming you store product_id in session
$variantId = (int)($_SESSION['variant_id'] ?? 0);

// Ensure transaction details are available
if (empty($oid) || empty($amt) || empty($refId) || empty($userId) || empty($productId)) {
    die("No transaction details found. Please contact support.");
}


// Insert the order into the orders table
$paymentStatus = 'success'; // Assuming payment is successful
$stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, variant_id, amount, transaction_id, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iiisss", $userId, $productId, $variantId, $amt, $oid, $paymentStatus);

if ($stmt->execute()) {
    // Success, you can show the success message
    echo "<h1>Payment Successful!</h1>";
    echo "<p>Thank you for your payment.</p>";
    echo "<p><strong>Transaction ID:</strong> " . htmlspecialchars($oid) . "</p>";
    echo "<p><strong>Amount Paid:</strong> Rs. " . htmlspecialchars($amt) . "</p>";
    echo "<p><strong>Reference ID:</strong> " . htmlspecialchars($refId) . "</p>";
    echo "<p>Your order has been successfully processed. You will receive a confirmation email shortly.</p>";
    echo "<p><a href='" . BASE_URL . "/index.php'>Return to Shop</a></p>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
