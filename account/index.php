<?php
require_once __DIR__ . '/../config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$user = $conn->query("SELECT first_name, last_name, email, username, phone, role FROM users WHERE id = $user_id")->fetch_assoc();
$addresses = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY id DESC");
$orders = $conn->query("
    SELECT o.id, p.name AS product_name, o.amount, o.payment_status, o.created_at
    FROM orders o LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = $user_id ORDER BY o.created_at DESC LIMIT 5
");

$fn = $user['first_name'] ?? '';
$ln = $user['last_name'] ?? '';
if ($fn === '' && $ln === '') { $fn = $user['username']; }
$initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
if (trim($initials) === '') { $initials = strtoupper(substr($user['username'], 0, 2)); }
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'];

$pageTitle = 'My Account';
$activePage = 'account';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-10">
    <!-- Profile header with avatar -->
    <div class="bg-white rounded-3xl border border-indigo-200 shadow-sm p-8 flex flex-col items-center text-center">
        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-600 to-indigo-700 text-white flex items-center justify-center text-3xl font-bold shadow">
            <?= htmlspecialchars($initials) ?>
        </div>
        <h1 class="text-2xl font-bold mt-4 text-slate-900"><?= htmlspecialchars($fullName) ?></h1>
        <p class="text-sm text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
        <span class="mt-2 inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($user['role'] === 'admin') ? 'bg-indigo-50 text-indigo-400' : 'bg-slate-100 text-slate-600' ?>">
            <?= htmlspecialchars($user['role'] ?? 'user') ?>
        </span>
        <div class="mt-5 flex gap-3">
            <a href="<?= BASE_URL ?>/cart/address.php" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-600 text-white text-sm font-semibold shadow transition">Add Address</a>
            <a href="<?= BASE_URL ?>/orders/index.php" class="px-5 py-2.5 rounded-xl border border-indigo-200 text-slate-700 text-sm font-medium hover:bg-indigo-50 transition">My Orders</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="px-5 py-2.5 rounded-xl border border-indigo-200 text-slate-700 text-sm font-medium hover:bg-indigo-50 transition">Admin Panel</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Saved addresses -->
        <div class="bg-white rounded-3xl border border-indigo-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-900">Saved Addresses</h2>
                <a href="<?= BASE_URL ?>/cart/address.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Add</a>
            </div>
            <?php if ($addresses && $addresses->num_rows > 0): while ($a = $addresses->fetch_assoc()): ?>
                <div class="border border-indigo-200 rounded-xl p-4 mb-3">
                    <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($a['province'] ?? '') ?>, <?= htmlspecialchars($a['city'] ?? '') ?></p>
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($a['address'] ?? '') ?></p>
                </div>
            <?php endwhile; else: ?>
                <p class="text-sm text-slate-400">No addresses saved yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent orders -->
        <div class="bg-white rounded-3xl border border-indigo-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-900">Recent Orders</h2>
                <a href="<?= BASE_URL ?>/orders/index.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all</a>
            </div>
            <?php if ($orders && $orders->num_rows > 0): while ($o = $orders->fetch_assoc()): ?>
                <div class="flex items-center justify-between border border-indigo-200 rounded-xl p-4 mb-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">#<?= $o['id'] ?> &middot; <?= htmlspecialchars($o['product_name'] ?? '—') ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($o['created_at']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">Rs. <?= htmlspecialchars($o['amount']) ?></p>
                        <span class="text-xs <?= $o['payment_status'] === 'Completed' ? 'text-emerald-600' : 'text-amber-600' ?>"><?= htmlspecialchars($o['payment_status']) ?></span>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p class="text-sm text-slate-400">No orders yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
