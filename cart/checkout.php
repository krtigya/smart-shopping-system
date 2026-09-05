<?php
require_once __DIR__ . '/../config/connection.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Address check
$addrRes = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id LIMIT 1");
if ($addrRes->num_rows === 0) {
    $qs = $_GET ? ('?from=checkout&' . http_build_query($_GET)) : '?from=checkout';
    header('Location: ' . BASE_URL . '/cart/address.php' . $qs);
    exit;
}
$address = $addrRes->fetch_assoc();

// Determine checkout mode: single product (buy now) or cart
$isSingle = !empty($_GET['product_id']) && is_numeric($_GET['product_id']);
$items = [];
$total = 0;

if ($isSingle) {
    $product_id = (int)$_GET['product_id'];
    $variant_id = (int)($_GET['variant_id'] ?? 0);
    $quantity = max(1, (int)($_GET['quantity'] ?? 1));
    $priceQuery = "SELECT price FROM product_variants WHERE id = $variant_id";
    $priceResult = $conn->query($priceQuery);
    $price = 0;
    if ($priceResult && $priceRow = $priceResult->fetch_assoc()) {
        $price = (float)$priceRow['price'];
    }
    if ($price == 0) {
        // fallback to product price
        $productPriceQuery = "SELECT price FROM products WHERE id = $product_id";
        $prodRes = $conn->query($productPriceQuery);
        if ($prodRes && $prodRow = $prodRes->fetch_assoc()) {
            $price = (float)$prodRow['price'];
        }
    }

    $sql = "SELECT p.id, p.name, p.image, v.size, v.uom, v.sku
            FROM products p
            LEFT JOIN product_variants v ON v.id = $variant_id AND v.product_id = p.id
            WHERE p.id = $product_id";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        $items[] = ['id' => $row['id'], 'name' => $row['name'], 'image' => $row['image'], 'size' => $row['size'], 'uom' => $row['uom'], 'sku' => $row['sku'], 'qty' => $quantity, 'price' => $price];
    }
    $total = $price * $quantity;
} else {
    // Cart checkout: fetch all items with price
    $res = $conn->query("
        SELECT p.id AS pid, p.name, p.image, v.size, v.uom, v.sku,
               COALESCE(v.price, p.price) AS price, c.quantity AS qty
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id AND c.variant_id = v.id
        WHERE c.user_id = $user_id
    ");
    while ($r = $res->fetch_assoc()) {
        $r['price'] = (float)($r['price'] ?? 0);
        $items[] = $r;
        $total += $r['price'] * (int)$r['qty'];
    }
}

$_SESSION['actual_amnt'] = $total;

// eSewa callback URLs (absolute)
$successurl = "http://localhost/Ecommerce%20website/orders/success.php?q=su";
$failedurl = "http://localhost/Ecommerce%20website/orders/failed.php?q=fu";

$pageTitle = 'Checkout';
$activePage = '';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-10">
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 mb-6 px-5 py-2.5 rounded-xl bg-white border border-indigo-200 text-slate-700 text-sm font-medium shadow-sm hover:bg-indigo-100 hover:text-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Continue Shopping
    </a>

    <h1 class="text-3xl font-bold mb-6 text-slate-800">Checkout</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Order Summary -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Order Summary</h2>
            <div class="space-y-4">
                <?php foreach ($items as $it): ?>
                <div class="flex items-center gap-4">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($it['image']) ?>" alt="" class="w-16 h-16 rounded-xl object-cover border border-indigo-200" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                    <div class="flex-1">
                        <a href="<?= BASE_URL ?>/products/view.php?id=<?= $it['id'] ?? $it['pid'] ?>" class="font-medium text-slate-800 hover:text-indigo-700 transition"><?= htmlspecialchars($it['name']) ?></a>
                        <?php if (!empty($it['size']) || !empty($it['uom'])): ?>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars(trim($it['size'] . (!empty($it['uom']) ? ' / ' . $it['uom'] : ''))) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($it['sku'])): ?>
                            <p class="text-xs text-slate-400">SKU: <?= htmlspecialchars($it['sku']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-indigo-700 mt-1">Rs. <?= number_format($it['price'], 2) ?> &times; <?= $it['qty'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <hr class="my-4 border-indigo-200">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-slate-900">Total</span>
                <span class="text-xl font-bold text-slate-900">Rs. <?= number_format($total, 2) ?></span>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-2">Shipping To</h2>
            <p class="text-sm text-slate-600"><?= htmlspecialchars($address['province'] ?? '') ?>, <?= htmlspecialchars($address['city'] ?? '') ?></p>
            <p class="text-sm text-slate-600"><?= htmlspecialchars($address['location'] ?? '') ?></p>
            <a href="<?= BASE_URL ?>/cart/address.php" class="text-sm font-medium text-indigo-700 hover:text-indigo-800 inline-block mt-2">Change</a>
        </div>
    </div>

    <!-- Payment -->
    <div class="mt-6 bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Payment Method</h2>
        <div class="flex flex-col sm:flex-row gap-3">
            <form id="esewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" target="_blank" class="flex-1">
                <input type="hidden" id="amount" name="amount" value="<?= number_format($total, 2, '.', '') ?>">
                <input type="hidden" id="tax_amount" name="tax_amount" value="0">
                <input type="hidden" id="total_amount" name="total_amount" value="<?= number_format($total, 2, '.', '') ?>">
                <input type="hidden" id="transaction_uuid" name="transaction_uuid" value="">
                <input type="hidden" id="product_code" name="product_code" value="EPAYTEST">
                <input type="hidden" id="product_service_charge" name="product_service_charge" value="0">
                <input type="hidden" id="product_delivery_charge" name="product_delivery_charge" value="0">
                <input type="hidden" id="success_url" name="success_url" value="<?= htmlspecialchars($successurl) ?>">
                <input type="hidden" id="failure_url" name="failure_url" value="<?= htmlspecialchars($failedurl) ?>">
                <input type="hidden" id="signed_field_names" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                <input type="hidden" id="signature" name="signature" value="">
                <input type="hidden" id="secret" name="secret" value="8gBm/:&EnhH.1/q">

                <button type="button" onclick="generateSignature(); document.getElementById('esewaForm').submit();" class="w-full px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h2V6a2 2 0 002-2h8a2 2 0 002 2v1H6v7h8V6a2 2 0 00-2-2H4z"/></svg>
                    Pay with eSewa
                </button>
            </form>

            <button type="button" onclick="openCodModal()" class="w-full sm:w-auto px-8 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                Cash on Delivery
            </button>
        </div>
    </div>
</div>

<!-- COD Confirmation Modal -->
<div id="codModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800">Confirm Cash on Delivery</h3>
        </div>
        <p class="text-sm text-slate-600 mb-4">You will pay <span class="font-semibold text-slate-900">Rs. <?= number_format($total, 2) ?></span> when your order is delivered to:</p>
        <div class="bg-indigo-50 rounded-xl p-3 text-sm text-slate-600 mb-5">
            <?= htmlspecialchars(($address['province'] ?? '') . ', ' . ($address['city'] ?? '')) ?><br>
            <?= htmlspecialchars($address['location'] ?? '') ?>
        </div>
        <form method="GET" action="<?= BASE_URL ?>/cart/place_cod.php" class="flex gap-3">
            <?php if ($isSingle): ?>
                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                <input type="hidden" name="variant_id" value="<?= $variant_id ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
            <?php endif; ?>
            <button type="button" onclick="closeCodModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-indigo-200 text-slate-700 font-medium hover:bg-indigo-50 transition">Cancel</button>
            <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold transition">Confirm Order</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
