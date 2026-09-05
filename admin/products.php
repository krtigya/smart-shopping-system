<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$added = isset($_GET['added']);
$products = $conn->query("
    SELECT p.id, p.name, p.price, p.quantity, p.image, p.description,
           c.name AS category, b.name AS brand
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY p.created_at DESC
");

$pageTitle  = 'Products';
$activePage = 'products';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($added): ?>
    <div class="mb-5 p-4 rounded-lg bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm">Product created successfully.</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900">All Products</h2>
        <a href="<?= BASE_URL ?>/admin/product_add.php" class="text-sm font-semibold text-white bg-pink-500 hover:bg-pink-600 px-4 py-2 rounded-xl">+ Add Product</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Product</th>
                    <th class="text-left font-medium px-5 py-3">Category</th>
                    <th class="text-left font-medium px-5 py-3">Brand</th>
                    <th class="text-right font-medium px-5 py-3">Base Price</th>
                    <th class="text-right font-medium px-5 py-3">Stock</th>
                    <th class="text-center font-medium px-5 py-3">Images</th>
                    <th class="text-center font-medium px-5 py-3">Variants</th>
                    <th class="text-right font-medium px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($products && $products->num_rows > 0): while ($p = $products->fetch_assoc()):
                    $pid = $p['id'];
                    $imgs = $conn->query("SELECT * FROM product_images WHERE product_id = $pid ORDER BY sort_order");
                    $vars = $conn->query("SELECT * FROM product_variants WHERE product_id = $pid ORDER BY id");
                    $imgCount = $imgs ? $imgs->num_rows : 0;
                    $varCount = $vars ? $vars->num_rows : 0;
                ?>
                <tr class="hover:bg-slate-50 align-top">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image']) ?>" alt="" class="w-12 h-12 rounded-lg object-cover border border-slate-200" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                            <div>
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($p['name']) ?></p>
                                <p class="text-xs text-slate-400 line-clamp-1 max-w-xs"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 60)) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($p['brand'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-right text-slate-700 font-medium">Rs. <?= htmlspecialchars($p['price']) ?></td>
                    <td class="px-5 py-3 text-right text-slate-700"><?= htmlspecialchars($p['quantity']) ?></td>
                    <td class="px-5 py-3 text-center text-slate-600"><?= $imgCount ?></td>
                    <td class="px-5 py-3 text-center text-slate-600"><?= $varCount ?></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <button type="button" onclick="toggleDetails(<?= $pid ?>)" class="text-pink-600 hover:text-pink-700 text-xs font-medium mr-3">Details</button>
                        <a href="<?= BASE_URL ?>/admin/product_edit.php?id=<?= $pid ?>" class="text-pink-600 hover:text-pink-700 text-xs font-medium mr-3">Edit</a>
                        <form method="POST" action="<?= BASE_URL ?>/admin/product_delete.php" class="inline" onsubmit="return confirm('Delete this product and all its images/variants?');">
                            <input type="hidden" name="productId" value="<?= $pid ?>">
                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr id="details-<?= $pid ?>" class="hidden bg-slate-50/60">
                    <td colspan="8" class="px-5 py-5">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-semibold uppercase text-slate-400 mb-2">Images &amp; Descriptions</h4>
                                <?php if ($imgCount > 0): ?>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <?php while ($img = $imgs->fetch_assoc()): ?>
                                    <figure class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($img['image']) ?>" class="w-full h-28 object-cover" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                                        <figcaption class="p-2 text-xs text-slate-600"><?= htmlspecialchars($img['description'] ?: 'No description') ?></figcaption>
                                    </figure>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?><p class="text-sm text-slate-400">No images.</p><?php endif; ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold uppercase text-slate-400 mb-2">Pricing &amp; Variants</h4>
                                <?php if ($varCount > 0): ?>
                                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-100 text-slate-500">
                                            <tr><th class="text-left px-3 py-2">Size</th><th class="text-left px-3 py-2">UOM</th><th class="text-left px-3 py-2">SKU</th><th class="text-right px-3 py-2">Price</th><th class="text-right px-3 py-2">Stock</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php while ($v = $vars->fetch_assoc()): ?>
                                            <tr>
                                                <td class="px-3 py-2"><?= htmlspecialchars($v['size'] ?: '—') ?></td>
                                                <td class="px-3 py-2"><?= htmlspecialchars($v['uom'] ?: '—') ?></td>
                                                <td class="px-3 py-2"><?= htmlspecialchars($v['sku'] ?: '—') ?></td>
                                                <td class="px-3 py-2 text-right">Rs. <?= htmlspecialchars($v['price']) ?></td>
                                                <td class="px-3 py-2 text-right"><?= htmlspecialchars($v['quantity']) ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?><p class="text-sm text-slate-400">No variants.</p><?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleDetails(id) {
        const el = document.getElementById('details-' + id);
        el.classList.toggle('hidden');
    }
</script>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
