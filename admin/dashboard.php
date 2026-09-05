<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Aggregate stats for the dashboard
$counts = [];
foreach (['products', 'categories', 'brands', 'orders', 'users'] as $t) {
    $r = $conn->query("SELECT COUNT(*) AS c FROM $t");
    $counts[$t] = $r ? (int)$r->fetch_assoc()['c'] : 0;
}
$rev = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM orders WHERE payment_status = 'Completed'");
$revenue = $rev ? (float)$rev->fetch_assoc()['total'] : 0;

$recent = $conn->query("
    SELECT o.id, p.name AS product, o.amount, o.payment_status, o.created_at
    FROM orders o LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC LIMIT 5
");

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$adminName = htmlspecialchars(trim(($_SESSION['first_name'] ?? '') ?: ($_SESSION['username'] ?? 'Admin')));
?>

<!-- Welcome banner -->
<div class="mb-8 rounded-2xl border border-pink-100 bg-white p-6 shadow-sm shadow-pink-100/50 sm:p-7">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-pink-600">Overview</p>
    <h2 class="mt-1.5 text-2xl font-bold text-slate-900">Welcome back, <?= $adminName ?></h2>
    <p class="mt-1 text-sm text-slate-500">Here's what's happening in your store today.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
    <?php
    $cards = [
        ['label' => 'Products',    'value' => $counts['products'],  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'accent' => 'bg-pink-50 text-pink-600'],
        ['label' => 'Categories',  'value' => $counts['categories'],'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'accent' => 'bg-rose-50 text-rose-600'],
        ['label' => 'Brands',      'value' => $counts['brands'],    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'accent' => 'bg-fuchsia-50 text-fuchsia-600'],
        ['label' => 'Orders',      'value' => $counts['orders'],    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'accent' => 'bg-pink-50 text-pink-600'],
        ['label' => 'Customers',   'value' => $counts['users'],     'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'accent' => 'bg-rose-50 text-rose-600'],
        ['label' => 'Revenue',     'value' => 'Rs. ' . number_format($revenue, 2), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1', 'accent' => 'bg-pink-100 text-pink-700', 'highlight' => true],
    ];
    foreach ($cards as $c):
    ?>
    <div class="group rounded-2xl border border-pink-100 bg-white p-5 shadow-sm shadow-pink-50 transition hover:shadow-md hover:shadow-pink-100/60 hover:-translate-y-0.5 <?= !empty($c['highlight']) ? 'ring-1 ring-pink-200' : '' ?>">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500"><?= $c['label'] ?></p>
                <p class="text-2xl font-bold text-slate-900 mt-1 tracking-tight"><?= htmlspecialchars($c['value']) ?></p>
            </div>
            <div class="w-11 h-11 rounded-xl <?= $c['accent'] ?> flex items-center justify-center transition group-hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $c['icon'] ?>"/></svg>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent orders -->
<div class="rounded-2xl border border-pink-100 bg-white shadow-sm shadow-pink-50 overflow-hidden">
    <div class="px-6 py-4 border-b border-pink-50 flex items-center justify-between bg-gradient-to-r from-pink-50/60 to-white">
        <div>
            <h2 class="font-semibold text-slate-900">Recent Orders</h2>
            <p class="text-xs text-slate-500 mt-0.5">Latest transactions from your store</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="text-sm font-semibold text-pink-600 hover:text-pink-700 transition">View all &rarr;</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-pink-50 bg-pink-50/30">
                    <th class="text-left font-semibold text-slate-600 px-6 py-3.5">Order</th>
                    <th class="text-left font-semibold text-slate-600 px-6 py-3.5">Product</th>
                    <th class="text-left font-semibold text-slate-600 px-6 py-3.5">Amount</th>
                    <th class="text-left font-semibold text-slate-600 px-6 py-3.5">Status</th>
                    <th class="text-left font-semibold text-slate-600 px-6 py-3.5">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
                <?php if ($recent && $recent->num_rows > 0): while ($o = $recent->fetch_assoc()): ?>
                <tr class="hover:bg-pink-50/40 transition-colors">
                    <td class="px-6 py-3.5 font-semibold text-slate-900">#<?= $o['id'] ?></td>
                    <td class="px-6 py-3.5 text-slate-600"><?= htmlspecialchars($o['product'] ?? '—') ?></td>
                    <td class="px-6 py-3.5 font-medium text-slate-800">Rs. <?= htmlspecialchars($o['amount']) ?></td>
                    <td class="px-6 py-3.5">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                            <?= $o['payment_status'] === 'Completed' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : ($o['payment_status'] === 'Failed' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100') ?>">
                            <?= htmlspecialchars($o['payment_status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-slate-500"><?= htmlspecialchars($o['created_at']) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
