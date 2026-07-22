<?php
/**
 * Cash on Delivery order placement.
 * Supports:
 *   - Single product (GET product_id, variant_id, quantity)
 *   - Full cart checkout (no product_id -> all items in cart)
 */
require_once __DIR__ . '/../config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Collect items to order
$items = [];

if (!empty($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    // Single product
    $product_id = (int)$_GET['product_id'];
    $variant_id = (int)($_GET['variant_id'] ?? 0);
    $quantity   = max(1, (int)($_GET['quantity'] ?? 1));

    $row = $conn->query("
        SELECT p.id AS pid, p.name, COALESCE(v.price, p.price) AS price,
               COALESCE(v.quantity, p.quantity) AS stock,
               v.quantity AS vstock
        FROM products p
        LEFT JOIN product_variants v ON v.id = $variant_id AND v.product_id = p.id
        WHERE p.id = $product_id
    ")->fetch_assoc();

    if (!$row) {
        die("Product not found.");
    }
    if ((int)$row['stock'] < $quantity) {
        die("Insufficient stock for " . htmlspecialchars($row['name']) . ".");
    }
    $items[] = [
        'product_id' => $product_id,
        'variant_id' => $variant_id,
        'quantity'   => $quantity,
        'price'      => (float)$row['price'],
    ];
} else {
    // Full cart
    $res = $conn->query("
        SELECT c.product_id, c.variant_id, c.quantity,
               COALESCE(v.price, p.price) AS price,
               COALESCE(v.quantity, p.quantity) AS stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id
        WHERE c.user_id = $user_id
    ");
    while ($row = $res->fetch_assoc()) {
        if ((int)$row['stock'] < (int)$row['quantity']) {
            die("Insufficient stock for one of your cart items.");
        }
        $items[] = [
            'product_id' => (int)$row['product_id'],
            'variant_id' => (int)$row['variant_id'],
            'quantity'   => (int)$row['quantity'],
            'price'      => (float)$row['price'],
        ];
    }
}

if (empty($items)) {
    die("No items to order.");
}

// Place each item as a COD order (payment_status = Pending)
$conn->begin_transaction();
try {
    foreach ($items as $it) {
        $pid = $it['product_id'];
        $vid = $it['variant_id'];
        $qty = $it['quantity'];
        $amt = $it['price'] * $qty;

        $conn->query("
            INSERT INTO orders (user_id, product_id, variant_id, amount, transaction_id, payment_status, order_status, created_at)
            VALUES ($user_id, $pid, $vid, $amt, 'COD', 'Pending', 'Pending', NOW())
        ");

        // Decrement stock
        if ($vid > 0) {
            $conn->query("UPDATE product_variants SET quantity = quantity - $qty WHERE id = $vid");
        }
        $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $pid");
    }

    // Clear cart if it was a cart checkout
    if (empty($_GET['product_id'])) {
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    die("Order failed: " . $e->getMessage());
}

$pageTitle = 'Order Placed';
$activePage = '';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm max-w-md w-full p-8 text-center">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
        <h1 class="text-2xl font-bold mb-2 text-slate-900">Order Placed!</h1>
        <p class="text-slate-600 mb-6">Your Cash on Delivery order has been placed successfully. Payment status is <span class="font-semibold">Pending</span> until delivery.</p>
        <div class="flex justify-center gap-3">
            <a href="<?= BASE_URL ?>/orders/index.php" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition">View My Orders</a>
            <a href="<?= BASE_URL ?>/index.php" class="px-5 py-2.5 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">Continue Shopping</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
