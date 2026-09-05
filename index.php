<?php
require_once __DIR__ . '/config/connection.php';

$isCatalog = isset($_GET['catalog']);
if (!$isCatalog && !isset($_GET['ajax'])) {
    $recentResult = $conn->query("SELECT p.id, p.name, p.price, p.description, p.image, p.quantity, c.name AS category, b.name AS brand FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE p.status = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT 6");
    $recentProducts = [];
    while ($recentResult && ($row = $recentResult->fetch_assoc())) $recentProducts[] = $row;
    
    $recommendedProducts = [];
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/includes/ImageSearchClient.php';
        try {
            $recommendationMatches = (new ImageSearchClient())->getRecommendations((int)$_SESSION['user_id'], 6);
            $ids = array_values(array_unique(array_filter(array_map(fn($m) => (int)($m['product_id'] ?? 0), $recommendationMatches))));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.description, p.image, p.quantity, c.name AS category, b.name AS brand FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE p.status = 1 AND p.id IN ($placeholders)");
                $types = str_repeat('i', count($ids)); $stmt->bind_param($types, ...$ids); $stmt->execute();
                $byId = [];
                foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $byId[(int)$row['id']] = $row;
                $seen = [];
                foreach ($recommendationMatches as $match) if (isset($byId[(int)$match['product_id']]) && empty($seen[(int)$match['product_id']])) { $seen[(int)$match['product_id']] = true; $recommendedProducts[] = $byId[(int)$match['product_id']]; }
            }
        } catch (Throwable $e) {}
    }

    $pageTitle = 'Home'; $activePage = 'home'; $searchQuery = '';
    include __DIR__ . '/includes/header.php';
    ?>
    <section class="store-hero"><div class="max-w-7xl mx-auto px-6 py-20 sm:py-28"><p class="text-sm font-semibold uppercase tracking-[0.25em] text-pink-600">The new collection</p><h1 class="mt-5 max-w-3xl text-5xl sm:text-7xl font-serif font-bold leading-tight text-slate-950">Best styles for every version of you.</h1><p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-700">Thoughtfully selected pieces for confident, modern living.</p><a href="<?= BASE_URL ?>/index.php?catalog=1" class="mt-8 inline-flex items-center gap-2 rounded-md bg-pink-500 px-7 py-3.5 text-sm font-semibold text-white hover:bg-pink-600 transition">Shop now <span aria-hidden="true">→</span></a></div></section>
    <?php if ($recommendedProducts): ?>
    <section class="max-w-7xl mx-auto px-6 py-16 bg-indigo-50/50 border-y border-indigo-100"><div class="flex items-end justify-between mb-8"><div><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">For You</p><h2 class="mt-2 text-3xl font-serif font-bold text-slate-950">Recommended for You</h2></div></div><div class="grid grid-cols-2 md:grid-cols-3 gap-5"><?php foreach ($recommendedProducts as $product): ?><a href="<?= BASE_URL ?>/products/view.php?id=<?= (int)$product['id'] ?>" class="group bg-white p-4 rounded-xl shadow-sm border border-indigo-100 hover:shadow-md transition"><div class="aspect-[4/5] overflow-hidden rounded-md bg-slate-100"><img loading="lazy" src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'"></div><p class="mt-4 text-xs uppercase tracking-wider text-indigo-600"><?= htmlspecialchars($product['category']) ?></p><h3 class="mt-1 font-serif text-lg font-semibold text-slate-950"><?= htmlspecialchars($product['name']) ?></h3><p class="mt-1 text-sm text-slate-600 font-medium">Rs. <?= number_format((float)$product['price'], 2) ?></p></a><?php endforeach; ?></div></section>
    <?php endif; ?>
    <section class="max-w-7xl mx-auto px-6 py-16"><div class="flex items-end justify-between mb-8"><div><p class="text-sm font-semibold uppercase tracking-widest text-pink-600">Just in</p><h2 class="mt-2 text-3xl font-serif font-bold text-slate-950">Recent arrivals</h2></div><a href="<?= BASE_URL ?>/index.php?catalog=1" class="text-sm font-semibold text-pink-600 hover:text-pink-700">View all products →</a></div><div class="grid grid-cols-2 md:grid-cols-3 gap-5"><?php foreach ($recentProducts as $product): ?><a href="<?= BASE_URL ?>/products/view.php?id=<?= (int)$product['id'] ?>" class="group"><div class="aspect-[4/5] overflow-hidden rounded-md bg-slate-100"><img loading="lazy" src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'"></div><p class="mt-3 text-xs uppercase tracking-wider text-pink-600"><?= htmlspecialchars($product['category']) ?></p><h3 class="mt-1 font-serif text-lg font-semibold text-slate-950"><?= htmlspecialchars($product['name']) ?></h3><p class="mt-1 text-sm text-slate-600">Rs. <?= number_format((float)$product['price'], 2) ?></p></a><?php endforeach; ?></div></section>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}
$categoryIds = array_values(array_filter(array_map('intval', (array)($_GET['category'] ?? []))));
$brandIds = array_values(array_filter(array_map('intval', (array)($_GET['brand'] ?? []))));
$stockFilter = $_GET['stock'] ?? 'all';
if (!in_array($stockFilter, ['all', 'in', 'out'], true)) $stockFilter = 'all';
$sort = $_GET['sort'] ?? 'newest';
$query = trim($_GET['query'] ?? '');
$visualSearch = isset($_GET['visual']);
$visualSearchIds = $visualSearch ? array_values(array_filter(array_map('intval', (array)($_SESSION['visual_search_ids'] ?? [])))) : [];
$visualSearchScores = $visualSearch ? (array)($_SESSION['visual_search_scores'] ?? []) : [];
$visualSearchImage = $visualSearch ? (string)($_SESSION['visual_search_image'] ?? '') : '';
$minPrice = is_numeric($_GET['min_price'] ?? null) ? max(0, (float)$_GET['min_price']) : null;
$maxPrice = is_numeric($_GET['max_price'] ?? null) ? max(0, (float)$_GET['max_price']) : null;
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = min(24, max(1, (int)($_GET['limit'] ?? 12)));
$sortSql = ['newest' => 'p.created_at DESC, p.id DESC', 'oldest' => 'p.created_at ASC, p.id ASC', 'price_low' => 'p.price ASC, p.id DESC', 'price_high' => 'p.price DESC, p.id DESC'][$sort] ?? 'p.created_at DESC, p.id DESC';
$where = ['p.status = 1'];
if ($categoryIds) $where[] = 'p.category_id IN (' . implode(',', $categoryIds) . ')';
if ($brandIds) $where[] = 'p.brand_id IN (' . implode(',', $brandIds) . ')';
if ($stockFilter === 'in') $where[] = 'p.quantity > 0';
if ($stockFilter === 'out') $where[] = 'p.quantity <= 0';
if ($minPrice !== null) $where[] = 'p.price >= ' . $minPrice;
if ($maxPrice !== null) $where[] = 'p.price <= ' . $maxPrice;
if ($visualSearch) $where[] = 'p.id IN (' . ($visualSearchIds ? implode(',', $visualSearchIds) : '0') . ')';
if ($query !== '') { $safeQuery = mysqli_real_escape_string($conn, $query); $where[] = "(p.name LIKE '%$safeQuery%' OR c.name LIKE '%$safeQuery%' OR b.name LIKE '%$safeQuery%')"; }
$whereSql = implode(' AND ', $where);
$orderSql = $visualSearch ? ('FIELD(p.id, ' . ($visualSearchIds ? implode(',', $visualSearchIds) : '0') . ')') : $sortSql;
$result = $conn->query("SELECT p.id, p.name, p.price, p.description, p.image, p.quantity, c.name AS category, b.name AS brand FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE $whereSql ORDER BY $orderSql LIMIT $limit OFFSET $offset");
if (!$result) die('Unable to load products: ' . htmlspecialchars($conn->error));
$products = [];
while ($row = $result->fetch_assoc()) $products[] = $row;
if ($visualSearch) foreach ($products as &$product) $product['visual_score'] = (float)($visualSearchScores[(int)$product['id']] ?? 0);
unset($product);
if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['products' => $products, 'hasMore' => count($products) === $limit]); exit; }
$categories = []; $categoryResult = $conn->query('SELECT id, name FROM categories ORDER BY name');
while ($categoryResult && ($row = $categoryResult->fetch_assoc())) $categories[] = $row;
$brands = []; $brandResult = $conn->query('SELECT id, name FROM brands ORDER BY name');
while ($brandResult && ($row = $brandResult->fetch_assoc())) $brands[] = $row;
$pageTitle = 'Products'; $activePage = 'products'; $searchQuery = $query;
include __DIR__ . '/includes/header.php';
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8"><p class="text-sm font-semibold uppercase tracking-wider text-indigo-700">Catalog</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900"><?= $visualSearch ? 'Similar products' : 'Products' ?></h1><p class="mt-2 text-slate-500"><?= $visualSearch ? 'Products visually similar to your uploaded image.' : 'Browse our collection using the filters below.' ?></p><?php if ($visualSearch): ?><a href="<?= BASE_URL ?>/index.php?catalog=1" class="mt-3 inline-block text-sm font-semibold text-pink-600 hover:text-pink-700">Clear image filter</a><?php endif; ?></div>
    <?php if ($visualSearch && $visualSearchImage): ?><div class="mb-8 flex items-center gap-4 rounded-xl border border-pink-100 bg-pink-50/40 p-4"><img src="<?= BASE_URL ?>/<?= htmlspecialchars($visualSearchImage) ?>" alt="Uploaded search image" class="h-24 w-24 rounded-lg object-cover border border-pink-200"><div><p class="text-sm font-semibold text-slate-800">Uploaded image</p><p class="mt-1 text-sm text-slate-500">Results are ordered by visual similarity.</p></div></div><?php endif; ?>
    <div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-8 items-start">
        <aside class="lg:sticky lg:top-28 lg:pr-8">
            <form id="catalogFilters" method="get" action="<?= BASE_URL ?>/index.php" class="space-y-6">
                <input type="hidden" name="catalog" value="1">
                <?php if ($query !== ''): ?><input type="hidden" name="query" value="<?= htmlspecialchars($query) ?>"><?php endif; ?>
                <?php if ($visualSearch): ?><input type="hidden" name="visual" value="1"><?php endif; ?>
                <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                <div><h2 class="text-sm font-semibold text-slate-900">Price</h2><div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2"><input type="number" min="0" step="0.01" name="min_price" value="<?= $minPrice !== null ? htmlspecialchars($minPrice) : '' ?>" placeholder="From" class="w-full rounded-md border border-slate-300 px-2.5 py-2 text-sm focus:border-pink-500 focus:ring-pink-500"><span class="text-slate-400">–</span><input type="number" min="0" step="0.01" name="max_price" value="<?= $maxPrice !== null ? htmlspecialchars($maxPrice) : '' ?>" placeholder="To" class="w-full rounded-md border border-slate-300 px-2.5 py-2 text-sm focus:border-pink-500 focus:ring-pink-500"></div></div>
                <div><h2 class="text-sm font-semibold text-slate-900">Category</h2><div class="mt-3 space-y-2 max-h-52 overflow-y-auto"><?php foreach ($categories as $category): ?><label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer"><input type="checkbox" name="category[]" value="<?= (int)$category['id'] ?>" <?= in_array((int)$category['id'], $categoryIds, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-indigo-700 focus:ring-indigo-600"><?= htmlspecialchars($category['name']) ?></label><?php endforeach; ?></div></div>
                <div class="border-t border-slate-200 pt-5"><h2 class="text-sm font-semibold text-slate-900">Brand</h2><div class="mt-3 space-y-2 max-h-52 overflow-y-auto"><?php foreach ($brands as $brand): ?><label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer"><input type="checkbox" name="brand[]" value="<?= (int)$brand['id'] ?>" <?= in_array((int)$brand['id'], $brandIds, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-indigo-700 focus:ring-indigo-600"><?= htmlspecialchars($brand['name']) ?></label><?php endforeach; ?></div></div>
                <div class="border-t border-slate-200 pt-5"><h2 class="text-sm font-semibold text-slate-900">Stock status</h2><div class="mt-3 space-y-2 text-sm text-slate-600"><?php foreach (['all' => 'All products', 'in' => 'In stock', 'out' => 'Out of stock'] as $value => $label): ?><label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="stock" value="<?= $value ?>" <?= $stockFilter === $value ? 'checked' : '' ?> class="border-slate-300 text-pink-500 focus:ring-pink-500"><?= $label ?></label><?php endforeach; ?></div></div>
                <button type="submit" class="w-full rounded-lg bg-pink-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-pink-600">Apply filters</button>
                <a href="<?= BASE_URL ?>/index.php?catalog=1" class="block text-center text-sm font-medium text-slate-500 hover:text-indigo-700">Clear all</a>
            </form>
        </aside>
        <section class="min-w-0">
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><p class="text-sm text-slate-500">Showing <?= count($products) ?> products</p><label class="flex items-center gap-2 text-sm text-slate-600">Sort by<select id="sortProducts" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-600 focus:ring-indigo-600"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option><option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option><option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option><option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option></select></label></div>
            <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5"><?php foreach ($products as $product): ?><article class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition"><a href="<?= BASE_URL ?>/products/view.php?id=<?= (int)$product['id'] ?>" class="block bg-slate-100 aspect-square overflow-hidden"><img loading="lazy" src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'"></a><div class="p-4"><p class="text-xs font-semibold uppercase tracking-wider text-indigo-700"><?= htmlspecialchars($product['category']) ?></p><h2 class="mt-1 font-semibold text-slate-900 line-clamp-2"><?= htmlspecialchars($product['name']) ?></h2><p class="mt-2 text-sm text-slate-500 line-clamp-2"><?= htmlspecialchars($product['description']) ?></p><div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3"><span class="font-bold text-slate-900">Rs. <?= number_format((float)$product['price'], 2) ?></span><span class="text-xs font-medium <?= (int)$product['quantity'] > 0 ? 'text-emerald-700' : 'text-rose-700' ?>"><?= (int)$product['quantity'] > 0 ? 'In stock' : 'Out of stock' ?></span><?php if ($visualSearch): ?><span class="text-xs font-semibold text-pink-600"><?= number_format((float)($product['visual_score'] ?? 0) * 100, 1) ?>% match</span><?php endif; ?></div></div></article><?php endforeach; ?></div>
            <div id="loadingProducts" class="hidden py-8 text-center text-sm text-slate-500">Loading more products…</div><div id="loadMoreSentinel" class="h-4" aria-hidden="true"></div><?php if (!$products): ?><div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">No products match these filters.</div><?php endif; ?>
        </section>
    </div>
</main>
<script>
(() => {
    const grid = document.getElementById('productGrid');
    const sentinel = document.getElementById('loadMoreSentinel');
    const loader = document.getElementById('loadingProducts');
    const sort = document.getElementById('sortProducts');
    const filters = document.getElementById('catalogFilters');
    const catalogBase = <?= json_encode(BASE_URL . '/index.php') ?>;
    let offset = <?= (int)count($products) ?>;
    let loading = false;
    let hasMore = <?= count($products) === $limit ? 'true' : 'false' ?>;

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[c]));

    function catalogParams() {
        const params = new URLSearchParams(new FormData(filters));
        params.set('sort', sort.value);
        ['min_price', 'max_price', 'query'].forEach((key) => {
            if (!String(params.get(key) || '').trim()) params.delete(key);
        });
        params.delete('ajax');
        params.delete('offset');
        params.delete('limit');
        return params;
    }

    function applyFilters() {
        window.location.href = catalogBase + '?' + catalogParams().toString();
    }

    function card(p) {
        const stock = Number(p.quantity) > 0;
        return '<article class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition">'
            + '<a href="<?= BASE_URL ?>/products/view.php?id=' + Number(p.id) + '" class="block bg-slate-100 aspect-square overflow-hidden">'
            + '<img loading="lazy" src="<?= BASE_URL ?>/' + esc(p.image) + '" alt="' + esc(p.name) + '" class="w-full h-full object-cover"></a>'
            + '<div class="p-4"><p class="text-xs font-semibold uppercase tracking-wider text-indigo-700">' + esc(p.category) + '</p>'
            + '<h2 class="mt-1 font-semibold text-slate-900 line-clamp-2">' + esc(p.name) + '</h2>'
            + '<p class="mt-2 text-sm text-slate-500 line-clamp-2">' + esc(p.description) + '</p>'
            + '<div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">'
            + '<span class="font-bold text-slate-900">Rs. ' + Number(p.price).toFixed(2) + '</span>'
            + '<span class="text-xs font-medium ' + (stock ? 'text-emerald-700' : 'text-rose-700') + '">' + (stock ? 'In stock' : 'Out of stock') + '</span>'
            + '</div></div></article>';
    }

    async function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        loader.classList.remove('hidden');
        const params = catalogParams();
        params.set('ajax', '1');
        params.set('offset', String(offset));
        params.set('limit', '<?= (int)$limit ?>');
        try {
            const response = await fetch(catalogBase + '?' + params.toString());
            const data = await response.json();
            (data.products || []).forEach((p) => grid.insertAdjacentHTML('beforeend', card(p)));
            offset += (data.products || []).length;
            hasMore = !!data.hasMore;
        } finally {
            loading = false;
            loader.classList.add('hidden');
        }
    }

    filters.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });
    filters.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
        input.addEventListener('change', applyFilters);
    });
    let priceTimer = null;
    filters.querySelectorAll('input[type="number"]').forEach((input) => {
        input.addEventListener('change', applyFilters);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyFilters();
            }
        });
        input.addEventListener('input', () => {
            clearTimeout(priceTimer);
            priceTimer = setTimeout(applyFilters, 600);
        });
    });
    sort.addEventListener('change', applyFilters);
    new IntersectionObserver((entries) => {
        entries.forEach((entry) => { if (entry.isIntersecting) loadMore(); });
    }, { rootMargin: '500px' }).observe(sentinel);
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
