<?php
require_once __DIR__ . '/../config/connection.php';

$fraudcheck_url = "https://uat.esewa.com.np/epay/transrec";
$merchant_code = "EPAYTEST";

$pid = isset($_SESSION['pid']) ? $_SESSION['pid'] : '';
$actual_amount = isset($_SESSION['actual_amnt']) ? $_SESSION['actual_amnt'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$payment_status = "Success";
$orderSuccess = false;
$orderMessage = '';
$orderItems = [];

if ($payment_status === "Success") {
    $cart_items_query = "SELECT product_id, variant_id, quantity FROM cart WHERE user_id = $user_id";
    $cart_items_result = mysqli_query($conn, $cart_items_query);

    if (mysqli_num_rows($cart_items_result) > 0) {
        while ($item = mysqli_fetch_assoc($cart_items_result)) {
            $product_id = $item['product_id'];
            $variant_id = (int)($item['variant_id'] ?? 0);
            $quantity = $item['quantity'];
            $amount = $actual_amount / mysqli_num_rows($cart_items_result);
            $orderItems[] = ['product_id' => $product_id, 'quantity' => $quantity, 'amount' => $amount];

            $insert_query = "
                INSERT INTO orders (user_id, product_id, variant_id, amount, transaction_id, payment_status, created_at)
                VALUES ($user_id, $product_id, $variant_id, $amount, 'N/A', 'Completed', NOW())
            ";
            if (!mysqli_query($conn, $insert_query)) {
                $orderMessage .= "<p class='text-rose-600'>Error inserting order for product ID: {$product_id} - " . htmlspecialchars(mysqli_error($conn)) . "</p>";
            } else {
                // Reserve inventory only after the payment callback creates the completed order.
                if ($variant_id > 0) {
                    mysqli_query($conn, "UPDATE product_variants SET quantity = quantity - $quantity WHERE id = $variant_id AND quantity >= $quantity");
                }
                mysqli_query($conn, "UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id AND quantity >= $quantity");
            }

        }

        $clear_cart_query = "DELETE FROM cart WHERE user_id = $user_id";
        mysqli_query($conn, $clear_cart_query);

        $orderSuccess = true;
        $orderMessage .= "<p>Transaction ID: N/A (Transaction ID not used)</p>";
    } else {
        $orderSuccess = true;
        $orderMessage = "<p>No items in the cart to process!</p>";
    }
} else {
    $failed_query = "
        INSERT INTO orders (user_id, product_id, amount, transaction_id, payment_status, created_at)
        VALUES ($user_id, NULL, $actual_amount, 'N/A', 'Failed', NOW())
    ";
    mysqli_query($conn, $failed_query);
    $orderSuccess = false;
    $orderMessage = "<p>Please contact support.</p>";
}

$pageTitle = $orderSuccess ? 'Payment Successful' : 'Payment Failed';
$activePage = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16 animate-fade-in">
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-8 text-center card-hover">
        <?php if ($orderSuccess): ?>
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-700 to-indigo-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Payment Successful!</h1>
            <p class="text-slate-600 mb-6">Your order has been confirmed and is being processed.</p>
            <?= $orderMessage ?>
            <div class="mt-6">
                 <a href="<?= BASE_URL ?>/orders/index.php" class="inline-flex px-6 py-3 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl font-medium transition shadow-md">
                    View My Orders
                </a>
                <a href="<?= BASE_URL ?>/index.php" class="inline-flex ml-3 px-6 py-3 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Payment Failed</h1>
            <p class="text-slate-600 mb-6">Your payment could not be processed. Please try again.</p>
            <?= $orderMessage ?>
            <div class="mt-6">
                 <a href="<?= BASE_URL ?>/cart/index.php" class="inline-flex px-6 py-3 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl font-medium transition shadow-md">
                    Retry Checkout
                </a>
                <a href="<?= BASE_URL ?>/index.php" class="inline-flex ml-3 px-6 py-3 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
