<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
$brands     = $conn->query("SELECT id, name FROM brands ORDER BY name");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id    = (int)($_POST['brand_id'] ?? 0);

    $imgFiles = $_FILES['images'] ?? [];
    $imgDesc  = $_POST['img_desc'] ?? [];
    $sizes    = $_POST['size'] ?? [];
    $uoms     = $_POST['uom'] ?? [];
    $prices   = $_POST['price'] ?? [];
    $qtys     = $_POST['qty'] ?? [];
    $skus     = $_POST['sku'] ?? [];

    if ($name === '' || $category_id === 0 || $brand_id === 0) {
        $message = 'Please provide product name, category and brand.';
    } elseif (empty($imgFiles['name'][0])) {
        $message = 'Please upload at least one product image.';
    } else {
        if (!is_dir(IMAGES_DIR)) {
            mkdir(IMAGES_DIR, 0755, true);
        }
        $firstPrice = 0;
        $totalQty   = 0;
        $firstImage = '';
        $imageData  = []; // collect valid uploaded rows

        foreach ($imgFiles['name'] as $i => $origName) {
            if ($origName === '') continue;
            $tmp  = $imgFiles['tmp_name'][$i];
            $chk  = getimagesize($tmp);
            if ($chk === false) { $message = "File '$origName' is not a valid image."; break; }
            $ext  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $message = "Unsupported image type: $ext"; break; }
            $newName = uniqid('prod_', true) . '.' . $ext;
            $dest = IMAGES_DIR . DIRECTORY_SEPARATOR . $newName;
            if (!move_uploaded_file($tmp, $dest)) { $message = "Failed to upload '$origName'."; break; }
            // Store a document-root-relative path; templates prepend BASE_URL.
            $webPath  = 'assets/images/' . $newName;
            $price    = (float)($prices[$i] ?? 0);
            $qty      = (int)($qtys[$i] ?? 0);
            if ($firstImage === '') { $firstImage = $webPath; $firstPrice = $price; }
            $totalQty += $qty;
            $imageData[] = [
                'web'  => $webPath,
                'desc' => $imgDesc[$i] ?? '',
                'size' => $sizes[$i] ?? '',
                'uom'  => $uoms[$i] ?? '',
                'sku'  => $skus[$i] ?? '',
                'price'=> $price,
                'qty'  => $qty,
            ];
        }

        if ($message === '' && count($imageData) > 0) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, category_id, brand_id, price, quantity, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssiidds", $name, $description, $category_id, $brand_id, $firstPrice, $totalQty, $firstImage);
            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;
                $imgStmt = $conn->prepare("INSERT INTO product_images (product_id, image, description, sort_order) VALUES (?, ?, ?, ?)");
                $varStmt = $conn->prepare("INSERT INTO product_variants (product_id, product_image_id, size, uom, sku, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($imageData as $sort => $d) {
                    $skuVal = $d['sku'] !== '' ? $d['sku']
                        : ('SKU-' . $product_id . '-' . ($sort + 1) . '-' . substr(uniqid(), -5));
                    $imgStmt->bind_param("issi", $product_id, $d['web'], $d['desc'], $sort);
                    $imgStmt->execute();
                    $image_id = $imgStmt->insert_id;
                    $varStmt->bind_param("iisssdi", $product_id, $image_id, $d['size'], $d['uom'], $skuVal, $d['price'], $d['qty']);
                    $varStmt->execute();
                }
                require_once __DIR__ . '/../includes/rebuild_visual_index.php';
                rebuild_visual_index();
                header('Location: ' . BASE_URL . '/admin/products.php?added=1');
                exit;
            } else {
                $message = 'Database error: ' . $conn->error;
            }
        }
    }
}

$pageTitle  = 'Add Product';
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
                <input name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="category_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-pink-500">
                        <option value="">Select</option>
                        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Brand</label>
                    <select name="brand_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-pink-500">
                        <option value="">Select</option>
                        <?php $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= (($_POST['brand_id'] ?? '') == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-pink-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900">Images, Descriptions &amp; Pricing</h2>
            <button type="button" id="addRow" class="text-sm font-medium text-pink-600 hover:text-pink-700">+ Add Image</button>
        </div>
        <p class="text-xs text-slate-500 mb-4">Each image can have its own description, size, unit of measure (UOM), price and stock. The first image becomes the product thumbnail.</p>

        <div id="rows" class="space-y-4">
            <!-- row template target populated by JS + one default row -->
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="<?= BASE_URL ?>/admin/products.php" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Cancel</a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-pink-500 hover:bg-pink-600 text-white text-sm font-semibold shadow">Save Product</button>
    </div>
</form>

<template id="rowTpl">
    <div class="row border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase">Image</span>
            <button type="button" class="rm text-xs text-rose-600 hover:text-rose-800">Remove</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Image File</label>
                <input type="file" name="images[]" accept="image/*" class="image-input block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-pink-50 file:text-pink-600 file:text-sm file:font-medium hover:file:bg-pink-100">
                <p class="image-preview mt-2 text-xs text-slate-500"></p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Image Description</label>
                <input name="img_desc[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Size</label>
                <input name="size[]" placeholder="e.g. M" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">UOM</label>
                <input name="uom[]" placeholder="e.g. pcs" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">SKU</label>
                <input name="sku[]" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Price (Rs.)</label>
                <input type="number" step="0.01" min="0" name="price[]" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Stock Qty</label>
                <input type="number" min="0" name="qty[]" value="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500">
            </div>
        </div>
    </div>
</template>

<script>
    const rows = document.getElementById('rows');
    const tpl  = document.getElementById('rowTpl');
    function addRow() {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.querySelector('.rm').addEventListener('click', () => node.remove());
        node.querySelector('.image-input').addEventListener('change', function () {
            const preview = node.querySelector('.image-preview');
            preview.textContent = Array.from(this.files).map(file => file.name).join(', ');
        });
        rows.appendChild(node);
    }
    document.getElementById('addRow').addEventListener('click', addRow);
    addRow(); // start with one row
</script>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
