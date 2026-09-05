<?php
    require_once __DIR__ . '/../config/connection.php';

    if (!isset($_SESSION['username'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "Invalid product ID."; exit;
    }
    $product_id = (int)$_GET['id'];

    $product = $conn->query("SELECT p.*, c.name AS category, b.name AS brand
        FROM products p LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = $product_id")->fetch_assoc();
    if (!$product) { echo "Product not found."; exit; }

    $variants = $conn->query("
        SELECT v.id AS vid, v.size, v.uom, v.sku, v.price, v.quantity AS stock,
               i.image, i.description
        FROM product_variants v
        JOIN product_images i ON v.product_image_id = i.id
        WHERE v.product_id = $product_id
        ORDER BY v.id
    ");
    $hasVariants = $variants && $variants->num_rows > 0;
    $variantList = $hasVariants ? $variants->fetch_all(MYSQLI_ASSOC) : [];
    if (!$hasVariants) {
        $variantList = [[
            'vid' => 0, 'size' => '', 'uom' => '', 'sku' => '', 'price' => $product['price'],
            'stock' => $product['quantity'], 'image' => $product['image'], 'description' => $product['description']
        ]];
    }
    $base = $variantList[0];

    if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_SESSION['username'])) {
            header("Location: " . BASE_URL . "/auth/login.php");
            exit;
        }

        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $variant_id = (int)($_POST['variant_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        if ($user_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }

        if ($quantity > 0) {
            if ($variant_id > 0) {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
                $stmt->bind_param("iiii", $user_id, $product_id, $variant_id, $quantity);
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, NULL, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            }
            $stmt->execute();

            if (isset($_POST['buy_now'])) {
                $vprice = 0;
                foreach ($variantList as $v) { if ($v['vid'] == $variant_id) { $vprice = $v['price']; break; } }
                if ($vprice == 0) $vprice = $product['price'];
                header("Location: " . BASE_URL . "/cart/checkout.php?product_id=$product_id&variant_id=$variant_id&quantity=$quantity&price=$vprice");
            } else {
                header("Location: " . BASE_URL . "/cart/index.php");
            }
            exit;
        } else {
            echo "Invalid quantity.";
        }
    }
    ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="<?= BASE_URL ?>/index.php" class="text-sm text-indigo-700 hover:text-indigo-800 mb-6 inline-flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Store
        </a>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 bg-white rounded-3xl border border-indigo-200 shadow-lg p-6 sm:p-8">
            <!-- Gallery -->
            <div>
                <div class="aspect-square overflow-hidden rounded-2xl border border-indigo-200 bg-indigo-50">
                    <img id="mainImg" src="<?= BASE_URL ?>/<?= !empty($base['image']) && $base['image'] !== 'image.png' ? htmlspecialchars($base['image']) : 'assets/images/no-image.svg' ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/images/no-image.svg';">
                </div>
                <div class="flex gap-3 mt-4 overflow-x-auto" id="thumbs">
                    <?php foreach ($variantList as $i => $v): ?>
                    <button type="button" onclick="selectVariant(<?= $i ?>)" class="thumb w-20 h-20 rounded-xl overflow-hidden border-2 <?= $i === 0 ? 'border-indigo-300' : 'border-indigo-200 hover:border-indigo-400' ?> transition">
                        <img src="<?= BASE_URL ?>/<?= !empty($v['image']) && $v['image'] !== 'image.png' ? htmlspecialchars($v['image']) : 'assets/images/no-image.svg' ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/images/no-image.svg';">
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Details -->
            <div class="flex flex-col">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700"><?= htmlspecialchars($product['brand'] ?? '') ?></span>
                <h1 class="text-3xl font-bold mt-1 text-slate-800"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="text-sm text-slate-500 mt-1">Category: <?= htmlspecialchars($product['category'] ?? '—') ?></p>

                <p class="text-2xl font-bold text-slate-800 mt-5">Rs. <span id="priceTag"><?= number_format($base['price'], 2) ?></span></p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex gap-2"><dt class="text-indigo-700 w-20">Size:</dt><dd id="sizeTag" class="text-slate-700 font-medium"><?= htmlspecialchars($base['size'] ?: '—') ?></dd></div>
                    <div class="flex gap-2"><dt class="text-indigo-700 w-20">UOM:</dt><dd id="uomTag" class="text-slate-700 font-medium"><?= htmlspecialchars($base['uom'] ?: '—') ?></dd></div>
                    <div class="flex gap-2"><dt class="text-indigo-700 w-20">SKU:</dt><dd id="skuTag" class="text-slate-700 font-medium"><?= htmlspecialchars($base['sku'] ?: '—') ?></dd></div>
                </dl>

                <p id="stockTag" class="text-xs mt-2 <?= $base['stock'] > 0 ? 'text-emerald-600' : 'text-rose-500' ?>">
                    <?= $base['stock'] > 0 ? $base['stock'] . ' in stock' : 'Out of stock' ?>
                </p>

                <p id="descTag" class="text-sm text-slate-600 mt-4 leading-relaxed"><?= nl2br(htmlspecialchars($base['description'] ?? $product['description'] ?? '')) ?></p>

                <form method="POST" class="mt-6 flex items-end gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Quantity</label>
                        <input type="number" name="quantity" id="qty" min="1" max="<?= max(1, $base['stock']) ?>" value="1" class="w-24 border border-indigo-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
                    </div>
                    <input type="hidden" name="variant_id" id="variantId" value="<?= $base['vid'] ?>">
                     <button type="submit" name="add_to_cart" class="flex-1 bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-3 rounded-xl transition shadow-md hover:shadow-lg">Add to Cart</button>
                     <button type="submit" name="buy_now" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 rounded-xl transition shadow-md hover:shadow-lg">Buy Now</button>
                </form>
                <p class="text-xs text-slate-400 mt-3">Select a variant image above to view and purchase that specific option.</p>
            </div>
        </div>
    </div>

    <script>
        const variants = <?= json_encode(array_map(function($v){
            $baseUrl = rtrim(BASE_URL, '/');
            $imgPath = (!empty($v['image']) && $v['image'] !== 'image.png') ? $v['image'] : 'assets/images/no-image.svg';
            $imgSrc = $baseUrl . '/' . $imgPath;
            return [
                'vid' => (int)$v['vid'],
                'price' => (float)$v['price'],
                'size' => $v['size'],
                'uom' => $v['uom'],
                'sku' => $v['sku'],
                'stock' => (int)$v['stock'],
                'image' => $imgSrc,
                'desc' => $v['description']
            ];
        }, $variantList)) ?>;

        function selectVariant(i) {
            const v = variants[i];
            document.getElementById('mainImg').src = v.image;
            document.getElementById('priceTag').textContent = Number(v.price).toFixed(2);
            document.getElementById('sizeTag').textContent = v.size || '—';
            document.getElementById('uomTag').textContent = v.uom || '—';
            document.getElementById('skuTag').textContent = v.sku || '-';
            document.getElementById('descTag').innerHTML = (v.desc || '').replace(/</g, '&lt;').replace(/\n/g, '<br>');
            const stock = v.stock;
            const stockEl = document.getElementById('stockTag');
            stockEl.textContent = stock > 0 ? stock + ' in stock' : 'Out of stock';
            stockEl.className = 'text-xs mt-2 ' + (stock > 0 ? 'text-emerald-600' : 'text-rose-500');
            const qty = document.getElementById('qty');
            qty.max = Math.max(1, stock);
            if (parseInt(qty.value, 10) > stock) qty.value = Math.max(1, stock);
            document.getElementById('variantId').value = v.vid;
            document.querySelectorAll('#thumbs .thumb').forEach((t, idx) => {
                t.classList.toggle('border-indigo-300', idx === i);
                t.classList.toggle('border-indigo-200', idx !== i);
            });
        }
    </script><!--

        function selectVariant(i) {
            const v = variants[i];
            document.getElementById('mainImg').src = v.image;
            document.getElementById('priceTag').textContent = Number(v.price).toFixed(2);
            document.getElementById('sizeTag').textContent = v.size || '—';
            document.getElementById('uomTag').textContent = v.uom || '—';
            document.getElementById('skuTag').textContent = v.sku || '—';
            document.getElementById('descTag').innerHTML = (v.desc || '').replace(/</g, '&lt;').replace(/\n/g, '<br>');
            const stock = v.stock;
            const stockEl = document.getElementById('stockTag');
            stockEl.textContent = stock > 0 ? stock + ' in stock' : 'Out of stock';
            stockEl.className = 'text-xs mt-2 ' + (stock > 0 ? 'text-emerald-600' : 'text-rose-500');
            const qty = document.getElementById('qty');
            qty.max = Math.max(1, stock);
            if (parseInt(qty.value, 10) > stock) qty.value = Math.max(1, stock);
            document.getElementById('variantId').value = v.vid;
            document.querySelectorAll('#thumbs .thumb').forEach((t, idx) => {
                t.classList.toggle('border-indigo-300', idx === i);
                t.classList.toggle('border-indigo-200', idx !== i);
            });
        }
    </script>-->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
