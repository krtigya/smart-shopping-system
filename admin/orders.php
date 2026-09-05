<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Delete
if (isset($_POST['deleteOrder']) && is_numeric($_POST['orderId'])) {
    $orderId = (int)$_POST['orderId'];
    $conn->query("UPDATE orders SET payment_status = 'Failed', order_status = 'Cancelled' WHERE id = $orderId");
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

if (isset($_POST['updateStatus']) && is_numeric($_POST['orderId'])) {
    $orderId = (int)$_POST['orderId'];
    $newStatus = $_POST['newStatus'] ?? 'Pending';
    $allowed = ['Pending', 'Completed', 'Failed'];
    if (!in_array($newStatus, $allowed, true)) $newStatus = 'Pending';
    $orderStatus = $newStatus === 'Completed' ? 'Processing' : ($newStatus === 'Failed' ? 'Cancelled' : 'Pending');
    $stmt = $conn->prepare('UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?');
    $stmt->bind_param('ssi', $newStatus, $orderStatus, $orderId);
    $stmt->execute();
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
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
                        <form method="POST" class="inline-flex items-center gap-2">
                            <select name="newStatus" class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs bg-white">
                                <option value="Pending" <?= $o['payment_status']=='Pending'?'selected':'' ?>>Pending</option>
                                <option value="Completed" <?= $o['payment_status']=='Completed'?'selected':'' ?>>Completed</option>
                                <option value="Failed" <?= $o['payment_status']=='Failed'?'selected':'' ?>>Failed</option>
                            </select>
                            <input type="hidden" name="orderId" value="<?= $o['id'] ?>">
                            <button type="submit" name="updateStatus" class="text-xs font-medium text-pink-600 hover:text-pink-700 transition">Update</button>
                        </form>
                        <?php if ($o['payment_status'] == 'Pending'): ?>
                            <form id="deleteForm<?= $o['id'] ?>" method="POST" class="inline ml-2">
                                <input type="hidden" name="orderId" value="<?= $o['id'] ?>">
                                <input type="hidden" name="deleteOrder" value="1">
                                <button type="button" onclick="openDeleteModal(<?= $o['id'] ?>)" class="text-xs font-medium text-rose-600 hover:text-rose-800 transition">Cancel</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="text-xs font-medium text-slate-400 cursor-not-allowed ml-2" disabled>Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900">Cancel Order</h3>
        </div>
        <p class="text-sm text-slate-600 mb-5">Are you sure you want to cancel this order? This action cannot be undone.</p>
        <div class="flex gap-3">
            <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition">Keep Order</button>
            <button type="button" id="confirmDeleteBtn" class="flex-1 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold transition">Yes, Cancel</button>
        </div>
    </div>
</div>

<script>
let deleteOrderId = null;

function openDeleteModal(orderId) {
    deleteOrderId = orderId;
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.getElementById('confirmDeleteBtn').onclick = function() {
        if (deleteOrderId !== null) {
            const form = document.getElementById('deleteForm' + deleteOrderId);
            if (form) form.submit();
        }
        closeDeleteModal();
    };
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    deleteOrderId = null;
}
</script>
<?php
require_once __DIR__ . '/../includes/admin_footer.php';
