<?php
require_once __DIR__ . '/../config/connection.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = (int)($_POST['cart_id'] ?? 0);

    if ($cartId > 0) {
        if ($action === 'remove') {
            $stmt = $conn->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $cartId, $user_id);
            $stmt->execute();
        } elseif ($action === 'update') {
            $quantity = (int)($_POST['quantity'] ?? 0);
            if ($quantity <= 0) {
                $stmt = $conn->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
                $stmt->bind_param('ii', $cartId, $user_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?');
                $stmt->bind_param('iii', $quantity, $cartId, $user_id);
                $stmt->execute();
            }
        }
    }

    header('Location: ' . BASE_URL . '/cart/index.php');
    exit;
}

$result = $conn->query("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.image,
           v.id AS variant_id, COALESCE(v.price, p.price) AS price, v.size, v.uom
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants v ON c.variant_id = v.id
    WHERE c.user_id = $user_id
    ORDER BY c.id DESC
");

$cart_items = [];
$total_cart_value = 0;
while ($result && ($row = $result->fetch_assoc())) {
    $cart_items[] = $row;
    $total_cart_value += ((float)$row['price']) * (int)$row['quantity'];
}

$pageTitle = 'Shopping Cart';
$activePage = 'cart';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Shopping Cart</h1>
        <p class="mt-2 text-slate-600">Review your items before checkout</p>
    </div>

    <?php if (count($cart_items) > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <?php foreach ($cart_items as $item):
                    $vprice = (float)$item['price'];
                ?>
                    <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex gap-6">
                            <div class="w-24 h-24 bg-indigo-50 rounded-xl overflow-hidden flex-shrink-0">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image']) ?>" alt="" class="w-full h-full object-cover" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-slate-800 mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                <?php if ($item['size'] || $item['uom']): ?>
                                    <p class="text-sm text-slate-500 mb-2"><?= htmlspecialchars(trim(($item['size'] ?: '') . ($item['uom'] ? ' / ' . $item['uom'] : ''))) ?></p>
                                <?php endif; ?>
                                <p class="text-indigo-700 font-semibold">Rs. <?= number_format($vprice, 2) ?></p>
                            </div>
                            <div class="flex flex-col items-end gap-3">
                                <form method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                    <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="1" class="w-20 border border-indigo-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
                                    <button type="submit" name="action" value="update" class="text-sm font-medium text-indigo-700 hover:text-indigo-800 transition">Update</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Remove this item from cart?')">
                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                    <button type="submit" name="action" value="remove" class="text-sm font-medium text-rose-600 hover:text-rose-800 transition">Remove</button>
                                </form>
                                <a href="<?= BASE_URL ?>/cart/checkout.php?product_id=<?= (int)$item['product_id'] ?>&variant_id=<?= (int)($item['variant_id'] ?? 0) ?>&quantity=<?= (int)$item['quantity'] ?>" class="text-xs font-medium text-emerald-600 hover:text-emerald-800 transition">Checkout this item</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm sticky top-24">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Order Summary</h3>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Subtotal</span>
                            <span class="font-semibold text-slate-900">Rs. <?= number_format($total_cart_value, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Shipping</span>
                            <span class="font-semibold text-slate-800">Free</span>
                        </div>
                        <div class="border-t border-indigo-100 pt-3 flex justify-between">
                            <span class="font-semibold text-slate-900">Total</span>
                            <span class="font-bold text-lg text-indigo-700">Rs. <?= number_format($total_cart_value, 2) ?></span>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/cart/checkout.php" class="block w-full text-center px-6 py-3 btn-indigo text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                        Proceed to Checkout
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?catalog=1" class="block w-full text-center mt-3 px-6 py-3 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-medium rounded-xl transition">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-16">
            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Your cart is empty</h3>
            <p class="text-slate-600 mb-6">Looks like you haven't added anything to your cart yet.</p>
            <a href="<?= BASE_URL ?>/index.php?catalog=1" class="inline-flex items-center px-6 py-3 btn-indigo text-white font-medium rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
