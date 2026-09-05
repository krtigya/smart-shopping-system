<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if (isset($_POST['deleteUser']) && is_numeric($_POST['userId'])) {
    $deleteId = (int)$_POST['userId'];
    $currentId = (int)($_SESSION['user_id'] ?? 0);
    if ($deleteId > 0 && $deleteId !== $currentId) {
        $target = $conn->query("SELECT role FROM users WHERE id = $deleteId")->fetch_assoc();
        $adminCount = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];
        if (!($target && $target['role'] === 'admin' && $adminCount <= 1)) {
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
        }
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$users = $conn->query("SELECT id, first_name, last_name, email, username, phone, role, created_at FROM users ORDER BY created_at DESC");

$pageTitle  = 'Users';
$activePage = 'users';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200"><h2 class="font-semibold text-slate-900">Registered Users</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">ID</th>
                    <th class="text-left font-medium px-5 py-3">Name</th>
                    <th class="text-left font-medium px-5 py-3">Username</th>
                    <th class="text-left font-medium px-5 py-3">Email</th>
                    <th class="text-left font-medium px-5 py-3">Phone</th>
                    <th class="text-left font-medium px-5 py-3">Role</th>
                    <th class="text-right font-medium px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($users && $users->num_rows > 0): while ($u = $users->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-slate-500"><?= $u['id'] ?></td>
                    <td class="px-5 py-3 font-medium text-slate-900"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            <?= $u['role'] === 'admin' ? 'bg-pink-50 text-pink-600' : 'bg-slate-100 text-slate-600' ?>">
                            <?= htmlspecialchars($u['role'] ?? 'user') ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <?php if ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)): ?>
                            <span class="text-xs text-slate-400">You</span>
                        <?php else: ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="userId" value="<?= $u['id'] ?>">
                            <button type="submit" name="deleteUser" class="text-rose-600 hover:text-rose-800 text-xs font-medium">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
