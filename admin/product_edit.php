<?php
require_once __DIR__ . '/../config/connection.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) { header('Location: ' . BASE_URL . '/admin/products.php'); exit; }

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
$brands     = $conn->query("SELECT id, name FROM brands ORDER BY name");
$images     = $conn->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order");
// variant per image
$variantByImage = [];
$allVars = $conn->query("SELECT * FROM product_variants WHERE product_id = $id");
if ($allVars) while ($v = $allVars->fetch_assoc()) { $variantByImage[$v['product_image_id']] = $v; }

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id    = (int)($_POST['brand_id'] ?? 0);

    if ($name === '' || $category_id === 0 || $brand_id === 0) {
        $message = 'Name, category and brand are required.';
    } else {
        // Update base product
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, category_id=?, brand_id=? WHERE id=?");
        $stmt->bind_param("ssiii", $name, $description, $category_id, $brand_id, $id);
        $stmt->execute();

        // Handle existing images
        $existingIds = $_POST['existing_id'] ?? [];
        $existingDesc = $_POST['existing_desc'] ?? [];
        $existingSize = $_POST['existing_size'] ?? [];
        $existingUom  = $_POST['existing_uom'] ?? [];
        $existingSku  = $_POST['existing_sku'] ?? [];
        $existingPrice= $_POST['existing_price'] ?? [];
        $existingQty  = $_POST['existing_qty'] ?? [];
        $deleteIds    = $_POST['delete_image'] ?? [];

        foreach ($existingIds as $idx => $imgId) {
            $imgId = (int)$imgId;
            if (in_array($imgId, $deleteIds, true)) {
                $r = $conn->query("SELECT image FROM product_images WHERE id = $imgId");
                if ($r && $row = $r->fetch_assoc()) {
                    $f = IMAGES_DIR . DIRECTORY_SEPARATOR . basename($row['image']);
                    if (file_exists($f)) { @unlink($f); }
                }
                $conn->query("DELETE FROM product_images WHERE id = $imgId"); // variants cascade (SET NULL then deleted via product? they reference product_image_id SET NULL)
                continue;
            }
            $d = $existingDesc[$idx] ?? '';
            $conn->query("UPDATE product_images SET description = '" . $conn->real_escape_string($d) . "' WHERE id = $imgId");
            $vid = $variantByImage[$imgId]['id'] ?? null;
            if ($vid) {
                $sz = $conn->real_escape_string($existingSize[$idx] ?? '');
                $uo = $conn->real_escape_string($existingUom[$idx] ?? '');
                $sk = trim($existingSku[$idx] ?? '');
                if ($sk === '') { $sk = 'SKU-' . $id . '-' . $vid . '-' . substr(uniqid(), -5); }
                $sk = $conn->real_escape_string($sk);
                $pr = (float)($existingPrice[$idx] ?? 0);
                $qt = (int)($existingQty[$idx] ?? 0);
                $conn->query("UPDATE product_variants SET size='$sz', uom='$uo', sku='$sk', price=$pr, quantity=$qt WHERE id = $vid");
            }
        }

        // New images
        $imgFiles = $_FILES['images'] ?? [];
        $imgDesc  = $_POST['img_desc'] ?? [];
        $sizes    = $_POST['size'] ?? [];
        $uoms     = $_POST['uom'] ?? [];
        $skus     = $_POST['sku'] ?? [];
        $prices   = $_POST['price'] ?? [];
        $qtys     = $_POST['qty'] ?? [];

        if (!empty($imgFiles['name'][0])) {
            foreach ($imgFiles['name'] as $i => $origName) {
                if ($origName === '') continue;
                $tmp = $imgFiles['tmp_name'][$i];
                if (!is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                $newName = uniqid('prod_', true) . '.' . $ext;
                if (!move_uploaded_file($tmp, IMAGES_DIR . DIRECTORY_SEPARATOR . $newName)) continue;
                $webPath = BASE_URL . '/assets/images/' . $newName;
                $conn->query("INSERT INTO product_images (product_id, image, description, sort_order) VALUES ($id, '" . $conn->real_escape_string($webPath) . "', '" . $conn->real_escape_string($imgDesc[$i] ?? '') . "', 99)");
                $newImgId = $conn->insert_id;
                $pr = (float)($prices[$i] ?? 0);
                $qt = (int)($qtys[$i] ?? 0);
                $sz = $conn->real_escape_string($sizes[$i] ?? '');
                $uo = $conn->real_escape_string($uoms[$i] ?? '');
                $skuVal = trim($skus[$i] ?? '');
                if ($skuVal === '') { $skuVal = 'SKU-' . $id . '-' . $newImgId . '-' . substr(uniqid(), -5); }
                $sk = $conn->real_escape_string($skuVal);
                $conn->query("INSERT INTO product_variants (product_id, product_image_id, size, uom, sku, price, quantity) VALUES ($id, $newImgId, '$sz', '$uo', '$sk', $pr, $qt)");
            }
        }

        // Recompute product price (min) & quantity (sum) and thumbnail
        $agg = $conn->query("SELECT MIN(price) AS minp, SUM(quantity) AS sumq, MIN(image) AS thumb FROM product_variants v JOIN product_images i ON v.product_image_id = i.id WHERE v.product_id = $id")->fetch_assoc();
        if ($agg && $agg['minp'] !== null) {
            $thumb = $agg['thumb'] ?: $product['image'];
            $conn->query("UPDATE products SET price=" . (float)$agg['minp'] . ", quantity=" . (int)$agg['sumq'] . ", image='" . $conn->real_escape_string($thumb) . "' WHERE id=$id");
        }

        header('Location: ' . BASE_URL . '/admin/products.php?added=1');
        exit;
    }
}

$pageTitle  = 'Edit Product';
$activePage = 'products';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($message): ?>
    <div class="mb-5 p-4 rounded-lg bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-slate-900 mb-4">Basic Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Product Name</label>
                <input name="name" required value="<?= htmlspecialchars($product['name']) ?>"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="category_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select</option>
                        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Brand</label>
                    <select name="brand_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select</option>
                        <?php $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= $product['brand_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-slate-900 mb-4">Existing Images &amp; Variants</h2>
        <div class="space-y-4">
            <?php
            $images->data_seek(0);
            while ($img = $images->fetch_assoc()):
                $v = $variantByImage[$img['id']] ?? null;
            ?>
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <div class="flex items-start gap-4">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($img['image']) ?>" class="w-20 h-20 rounded-lg object-cover border border-slate-200" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                        <input type="hidden" name="existing_id[]" value="<?= $img['id'] ?>">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                            <input name="existing_desc[]" value="<?= htmlspecialchars($img['description'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div><label class="block text-xs font-medium text-slate-600 mb-1">Size</label><input name="existing_size[]" value="<?= htmlspecialchars($v['size'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-slate-600 mb-1">UOM</label><input name="existing_uom[]" value="<?= htmlspecialchars($v['uom'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-slate-600 mb-1">SKU</label><input name="existing_sku[]" value="<?= htmlspecialchars($v['sku'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-slate-600 mb-1">Price</label><input type="number" step="0.01" name="existing_price[]" value="<?= htmlspecialchars($v['price'] ?? 0) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-slate-600 mb-1">Stock</label><input type="number" name="existing_qty[]" value="<?= htmlspecialchars($v['quantity'] ?? 0) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                    </div>
                </div>
                <label class="mt-3 inline-flex items-center gap-2 text-xs text-rose-600">
                    <input type="checkbox" name="delete_image[]" value="<?= $img['id'] ?>"> Delete this image &amp; its variant
                </label>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900">Add More Images</h2>
            <button type="button" id="addRow" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Add Image</button>
        </div>
        <div id="rows" class="space-y-4"></div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="<?= BASE_URL ?>/admin/products.php" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Cancel</a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-600 text-white text-sm font-semibold shadow">Update Product</button>
    </div>
</form>

<template id="rowTpl">
    <div class="row border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase">New Image</span>
            <button type="button" class="rm text-xs text-rose-600 hover:text-rose-800">Remove</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Image File</label><input type="file" name="images[]" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-400 file:text-sm"></div>
            <div class="md:col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Description</label><input name="img_desc[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Size</label><input name="size[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">UOM</label><input name="uom[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">SKU</label><input name="sku[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Price</label><input type="number" step="0.01" name="price[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Stock</label><input type="number" name="qty[]" value="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>
</template>
<script>
    const rows = document.getElementById('rows');
    const tpl  = document.getElementById('rowTpl');
    function addRow(){ const n = tpl.content.firstElementChild.cloneNode(true); n.querySelector('.rm').addEventListener('click',()=>n.remove()); rows.appendChild(n); }
    document.getElementById('addRow').addEventListener('click', addRow);
</script>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
