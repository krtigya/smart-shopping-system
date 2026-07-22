<?php
require_once __DIR__ . '/../config/connection.php';

// Delete
if (isset($_POST['deleteOrder']) && is_numeric($_POST['orderId'])) {
    $conn->query("DELETE FROM orders WHERE id = " . (int)$_POST['orderId']);
}
// Update status
if (isset($_POST['updateStatus']) && is_numeric($_POST['orderId'])) {
    $newStatus = $conn->real_escape_string($_POST['newStatus']);
    $conn->query("UPDATE orders SET payment_status = '$newStatus' WHERE id = " . (int)$_POST['orderId']);
}

$orders = $conn->query("SELECT o.*, p.name AS product_name, u.username, u.email FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");

$pageTitle  = 'Orders';
$activePage = 'orders';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200"><h2 class="font-semibold text-slate-900">All Orders</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Order</th>
                    <th class="text-left font-medium px-5 py-3">Customer</th>
                    <th class="text-left font-medium px-5 py-3">Product</th>
                    <th class="text-right font-medium px-5 py-3">Amount</th>
                    <th class="text-left font-medium px-5 py-3">Status</th>
                    <th class="text-left font-medium px-5 py-3">Date</th>
                    <th class="text-right font-medium px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($orders && $orders->num_rows > 0): while ($o = $orders->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">#<?= $o['id'] ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($o['username'] ?? '—') ?><br><span class="text-xs text-slate-400"><?= htmlspecialchars($o['email'] ?? '') ?></span></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($o['product_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-right text-slate-700">Rs. <?= htmlspecialchars($o['amount']) ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            <?= $o['payment_status'] === 'Completed' ? 'bg-emerald-50 text-emerald-700' : ($o['payment_status'] === 'Failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') ?>">
                            <?= htmlspecialchars($o['payment_status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= htmlspecialchars($o['created_at']) ?></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <form method="POST" class="inline-flex items-center gap-2" onsubmit="return confirm('Delete this order?');">
                            <select name="newStatus" class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs bg-white">
                                <option value="Pending" <?= $o['payment_status']=='Pending'?'selected':'' ?>>Pending</option>
                                <option value="Completed" <?= $o['payment_status']=='Completed'?'selected':'' ?>>Completed</option>
                                <option value="Failed" <?= $o['payment_status']=='Failed'?'selected':'' ?>>Failed</option>
                            </select>
                            <input type="hidden" name="orderId" value="<?= $o['id'] ?>">
                            <button type="submit" name="updateStatus" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Update</button>
                            <?php if ($o['payment_status'] == 'Pending'): ?>
    <button type="submit" name="deleteOrder" class="text-xs font-medium text-rose-600 hover:text-rose-800">Cancel</button>
<?php else: ?>
    <button type="button" class="text-xs font-medium text-rose-400 cursor-not-allowed" disabled>Cancel</button>
<?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
