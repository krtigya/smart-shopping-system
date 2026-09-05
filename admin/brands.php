<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Delete
if (isset($_POST['deleteBrand']) && is_numeric($_POST['brandId'])) {
    $conn->query("DELETE FROM brands WHERE id = " . (int)$_POST['brandId']);
    header('Location: ' . BASE_URL . '/admin/brands.php');
    exit;
}

$brands = $conn->query("SELECT * FROM brands ORDER BY name");

$pageTitle  = 'Brands';
$activePage = 'brands';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Brands</h2>
        <p class="text-sm text-slate-500">Manage product brands and their details.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/brand_add.php" class="text-sm font-semibold text-white bg-pink-500 hover:bg-pink-600 px-4 py-2 rounded-xl">+ Add Brand</a>
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
                <?php if ($brands && $brands->num_rows > 0): while ($b = $brands->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-slate-500"><?= $b['id'] ?></td>
                    <td class="px-5 py-3 font-medium text-slate-900"><?= htmlspecialchars($b['name']) ?></td>
                    <td class="px-5 py-3 text-slate-600 max-w-xs"><?= htmlspecialchars(substr($b['description'] ?? '', 0, 80)) ?: '—' ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= ($b['status'] ?? 1) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                            <?= ($b['status'] ?? 1) ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/admin/brand_add.php?edit=<?= $b['id'] ?>" class="text-pink-600 hover:text-pink-700 text-xs font-medium mr-3">Edit</a>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this brand?');">
                            <input type="hidden" name="brandId" value="<?= $b['id'] ?>">
                            <button type="submit" name="deleteBrand" class="text-rose-600 hover:text-rose-800 text-xs font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No brands found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
