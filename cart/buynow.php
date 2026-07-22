<?php
require_once __DIR__ . '/../config/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if product_id is passed
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$product_id = (int)$_GET['product_id'];
$variant_id = isset($_GET['variant_id']) && is_numeric($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;

// Fetch product price and current quantity
$product_query = "SELECT price, quantity FROM products WHERE id = $product_id";
$product_result = mysqli_query($conn, $product_query);

if ($product_result && mysqli_num_rows($product_result) > 0) {
    $product = mysqli_fetch_assoc($product_result);
    $price = $product['price'];
    $current_quantity = $product['quantity'];

    if ($current_quantity <= 0) {
        $pageTitle = 'Out of Stock';
        $activePage = '';
        include __DIR__ . '/../includes/header.php';
        ?>
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm max-w-md w-full p-8 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <h1 class="text-2xl font-bold mb-2 text-slate-900">Product Out of Stock!</h1>
                <p class="text-slate-600 mb-6">We're sorry, this product is no longer available.</p>
                <a href="<?= BASE_URL ?>/index.php" class="inline-block px-5 py-2.5 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition">Continue Shopping</a>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/footer.php';
    } else {
        // Insert order details
        $order_query = "
            INSERT INTO orders (user_id, product_id, variant_id, amount, transaction_id, payment_status, created_at)
            VALUES ($user_id, $product_id, $variant_id, $price, 0, 'Pending', NOW())
        ";

        if (mysqli_query($conn, $order_query)) {
            // Decrease product quantity by 1
            $new_quantity = $current_quantity - 1;
            $update_quantity_query = "UPDATE products SET quantity = $new_quantity WHERE id = $product_id";

            if (mysqli_query($conn, $update_quantity_query)) {
                // Remove the product from the cart
                $delete_cart_query = "DELETE FROM cart WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id";
                if (mysqli_query($conn, $delete_cart_query)) {
                    $pageTitle = 'Order Placed';
                    $activePage = '';
                    include __DIR__ . '/../includes/header.php';
                    ?>
                    <div class="min-h-screen flex items-center justify-center px-4">
                        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm max-w-md w-full p-8 text-center">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </div>
                            <h1 class="text-2xl font-bold mb-2 text-slate-900">Order Placed Successfully!</h1>
                            <p class="text-slate-600 mb-2">Your order for product ID <?= $product_id ?> has been placed with Cash on Delivery.</p>
                            <p class="text-slate-600 mb-6">Payment Status: <span class="font-semibold">Pending</span></p>
                            <a href="<?= BASE_URL ?>/orders/index.php" class="inline-block px-5 py-2.5 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition mr-2">View My Orders</a>
                            <a href="<?= BASE_URL ?>/index.php" class="inline-block px-5 py-2.5 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">Continue Shopping</a>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../includes/footer.php';
                } else {
                    die("Error Removing from Cart! " . mysqli_error($conn));
                }
            } else {
                die("Error Updating Quantity! " . mysqli_error($conn));
            }
        } else {
            die("Error Placing Order! " . mysqli_error($conn));
        }
    }
} else {
    $pageTitle = 'Product Not Found';
    $activePage = '';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
            </div>
            <h1 class="text-2xl font-bold mb-2 text-slate-900">Product Not Found!</h1>
            <p class="text-slate-600 mb-6">Please try again.</p>
            <a href="<?= BASE_URL ?>/index.php" class="inline-block px-5 py-2.5 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition">Continue Shopping</a>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php';
}

// Close database connection
mysqli_close($conn);
?>
