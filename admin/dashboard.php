<?php
require_once __DIR__ . '/../config/connection.php';

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
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
    <?php
    $cards = [
        ['label' => 'Products',    'value' => $counts['products'],  'color' => 'indigo', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => 'Categories',  'value' => $counts['categories'],'color' => 'sky',    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
        ['label' => 'Brands',      'value' => $counts['brands'],    'color' => 'emerald','icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
        ['label' => 'Orders',      'value' => $counts['orders'],    'color' => 'amber',  'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['label' => 'Customers',   'value' => $counts['users'],     'color' => 'violet', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['label' => 'Revenue',     'value' => 'Rs. ' . number_format($revenue, 2), 'color' => 'rose', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500"><?= $c['label'] ?></p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= htmlspecialchars($c['value']) ?></p>
            </div>
            <div class="w-11 h-11 rounded-lg bg-<?= $c['color'] ?>-50 flex items-center justify-center">
                <svg class="w-6 h-6 text-<?= $c['color'] ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $c['icon'] ?>"/></svg>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900">Recent Orders</h2>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Order</th>
                    <th class="text-left font-medium px-5 py-3">Product</th>
                    <th class="text-left font-medium px-5 py-3">Amount</th>
                    <th class="text-left font-medium px-5 py-3">Status</th>
                    <th class="text-left font-medium px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($recent && $recent->num_rows > 0): while ($o = $recent->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">#<?= $o['id'] ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($o['product'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-600">Rs. <?= htmlspecialchars($o['amount']) ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            <?= $o['payment_status'] === 'Completed' ? 'bg-emerald-50 text-emerald-700' : ($o['payment_status'] === 'Failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') ?>">
                            <?= htmlspecialchars($o['payment_status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= htmlspecialchars($o['created_at']) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
