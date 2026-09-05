<?php
require_once __DIR__ . '/../config/connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Handle order cancellation (Admin only - users cannot cancel)
if (isset($_POST['cancel_order']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $order_id = (int)$_POST['order_id'];

    $order_query = "SELECT product_id, amount FROM orders WHERE id = $order_id AND user_id = $user_id";
    $order_result = mysqli_query($conn, $order_query);

    if ($order_result && mysqli_num_rows($order_result) > 0) {
        $order = mysqli_fetch_assoc($order_result);
        $product_id = $order['product_id'];
        $amount = $order['amount'];

        $product_query = "SELECT quantity FROM products WHERE id = $product_id";
        $product_result = mysqli_query($conn, $product_query);

        if ($product_result && mysqli_num_rows($product_result) > 0) {
            $product = mysqli_fetch_assoc($product_result);
            $new_quantity = $product['quantity'] + 1;

            $update_quantity_query = "UPDATE products SET quantity = $new_quantity WHERE id = $product_id";
            if (mysqli_query($conn, $update_quantity_query)) {
                $delete_order_query = "DELETE FROM orders WHERE id = $order_id AND user_id = $user_id";
                if (mysqli_query($conn, $delete_order_query)) {
                    header('Location: ' . BASE_URL . '/orders/index.php');
                    exit;
                }
            }
        }
    }
}

// Fetch orders for the user with complete details
$order_query = "
    SELECT orders.id, products.name AS product_name, products.image AS product_image,
           orders.amount, orders.payment_status, orders.created_at, orders.order_status,
           orders.transaction_id, orders.variant_id,
           COALESCE(CONCAT(product_variants.size, ' ', product_variants.uom), 'Standard') AS variant_info
    FROM orders
    JOIN products ON orders.product_id = products.id
    LEFT JOIN product_variants ON orders.variant_id = product_variants.id AND orders.product_id = product_variants.product_id
    WHERE orders.user_id = $user_id
    ORDER BY orders.created_at DESC
";

$order_result = mysqli_query($conn, $order_query);

if (!$order_result) {
    die("Error fetching orders: " . mysqli_error($conn));
}

$orders = mysqli_fetch_all($order_result, MYSQLI_ASSOC);
$total_orders = count($orders);

$pageTitle = 'My Orders';
$activePage = 'orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">My Orders</h1>
        <p class="mt-2 text-slate-600">View and manage your order history</p>
    </div>

    <!-- Order Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Orders</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1"><?= $total_orders ?></p>
                </div>
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                     <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Pending</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">
                        <?php
                        $pending = 0;
                        foreach($orders as $row) {
                            if($row['payment_status'] == 'Pending') $pending++;
                        }
                        echo $pending;
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Paid</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">
                        <?php
                        $paid = 0;
                        foreach($orders as $row) {
                            if($row['payment_status'] == 'Paid') $paid++;
                        }
                        echo $paid;
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Spent</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">
                        Rs. <?php
                        $total = 0;
                        foreach($orders as $row) {
                            $total += $row['amount'];
                        }
                        echo number_format($total, 2);
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                     <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-indigo-100">
            <h2 class="text-lg font-semibold text-slate-800">Order History</h2>
            <p class="text-sm text-slate-600 mt-1">Manage and track all your orders</p>
        </div>
        
        <div class="overflow-x-auto">
            <?php if ($total_orders > 0): ?>
                <table class="w-full">
                    <thead class="bg-indigo-50 border-b border-indigo-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Variant</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Transaction</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100">
                        <?php foreach ($orders as $order): 
                            $paymentStatus = strtolower($order['payment_status']);
                            $orderStatus = strtolower($order['order_status'] ?? 'pending');
                            $canCancel = in_array($paymentStatus, ['pending', 'cod']);
                        ?>
                            <tr class="hover:bg-indigo-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                            <span class="text-indigo-400 font-semibold text-sm">#<?= htmlspecialchars($order['id']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <?php if (!empty($order['product_image'])): ?>
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($order['product_image']) ?>" alt="<?= htmlspecialchars($order['product_name']) ?>" class="w-full h-full object-cover rounded-xl" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                                            <?php else: ?>
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($order['product_name']) ?></div>
                                            <?php if (!empty($order['variant_info'])): ?>
                                                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($order['variant_info']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($order['variant_info']) && $order['variant_info'] != 'Standard'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-400">
                                            <?= htmlspecialchars($order['variant_info']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">Rs. <?= number_format($order['amount'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                        <?= htmlspecialchars($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                        <?= htmlspecialchars($order['order_status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php if (!empty($order['transaction_id']) && $order['transaction_id'] != '0' && $order['transaction_id'] != 'COD'): ?>
                                        <span class="font-mono text-xs"><?= htmlspecialchars($order['transaction_id']) ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <div><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                    <div class="text-xs text-slate-500"><?= date('g:i A', strtotime($order['created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                     <a href="<?= BASE_URL ?>/orders/order_details.php?id=<?= $order['id'] ?>" class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-800 text-indigo-700 hover:text-white text-sm font-medium rounded-xl transition-all duration-200 shadow-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Details
                                    </a>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $canCancel): ?>
                                        <form method="POST" class="inline ml-2" onsubmit="return confirm('Cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="cancel_order" class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white text-sm font-medium rounded-xl transition-all duration-200 shadow-sm">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">No orders yet</h3>
                    <p class="text-slate-600 mb-6">You haven't placed any orders. Start shopping to see your orders here.</p>
                     <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center px-6 py-3 bg-indigo-700 hover:bg-indigo-800 text-white font-medium rounded-xl transition shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Back to Shopping -->
    <div class="mt-8 text-center">
        <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center text-indigo-700 hover:text-indigo-800 font-medium transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Continue Shopping
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
