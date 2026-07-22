<?php
require_once __DIR__ . '/../config/connection.php';

if (isset($_POST['deleteCategory']) && is_numeric($_POST['categoryId'])) {
    $conn->query("DELETE FROM categories WHERE id = " . (int)$_POST['categoryId']);
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$pageTitle  = 'Categories';
$activePage = 'categories';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Categories</h2>
        <p class="text-sm text-slate-500">Organize products into categories.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/category_add.php" class="text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-600 px-4 py-2 rounded-xl">+ Add Category</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">ID</th>
                    <th class="text-left font-medium px-5 py-3">Name</th>
                    <th class="text-left font-medium px-5 py-3">Description</th>
                    <th class="text-left font-medium px-5 py-3">Status</th>
                    <th class="text-right font-medium px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($categories && $categories->num_rows > 0): while ($c = $categories->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-slate-500"><?= $c['id'] ?></td>
                    <td class="px-5 py-3 font-medium text-slate-900"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="px-5 py-3 text-slate-600 max-w-xs"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 80)) ?: '—' ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= ($c['status'] ?? 1) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                            <?= ($c['status'] ?? 1) ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/admin/category_add.php?edit=<?= $c['id'] ?>" class="text-sky-600 hover:text-sky-800 text-xs font-medium mr-3">Edit</a>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="categoryId" value="<?= $c['id'] ?>">
                            <button type="submit" name="deleteCategory" class="text-rose-600 hover:text-rose-800 text-xs font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
