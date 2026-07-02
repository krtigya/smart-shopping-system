<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');

// ---- Read filters from query string ----
$search     = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$brandId    = isset($_GET['brand_id']) && $_GET['brand_id'] !== '' ? (int)$_GET['brand_id'] : null;

// ---- Build product query dynamically & safely (prepared statement) ----
$where  = ['p.status = 1'];
$types  = '';
$params = [];

if ($search !== '') {
    $where[] = '(p.product_name LIKE ? OR p.description LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($categoryId) {
    $where[] = 'p.category_id = ?';
    $types .= 'i';
    $params[] = $categoryId;
}
if ($brandId) {
    $where[] = 'p.brand_id = ?';
    $types .= 'i';
    $params[] = $brandId;
}

$sql = "SELECT p.*, c.category_name, b.brand_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN brands b ON b.id = p.brand_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC LIMIT 24";

$stmtP = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmtP, $types, ...$params);
}
mysqli_stmt_execute($stmtP);
$products = mysqli_stmt_get_result($stmtP);

// ---- Sidebar filter data ----
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY category_name ASC");
$brands     = mysqli_query($conn, "SELECT * FROM brands WHERE status = 1 ORDER BY brand_name ASC");

$stmt = mysqli_prepare($conn, "SELECT created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$memberSince = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$totalCategories = mysqli_num_rows($categories);
mysqli_data_seek($categories, 0);

$initials = '';
foreach (explode(' ', trim($_SESSION['name'])) as $part) {
    if ($part !== '') { $initials .= mb_strtoupper(mb_substr($part, 0, 1)); }
    if (mb_strlen($initials) >= 2) break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - SmartShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-pink-50">

<nav class="bg-white border-b border-pink-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center gap-4 h-20">

            <a href="../index.php" class="text-2xl font-extrabold text-pink-600 shrink-0">SmartShop</a>

            <!-- SEARCH BAR -->
            <form method="GET" action="" class="flex-1 max-w-2xl">
                <div class="relative flex items-center">
                    <svg class="absolute left-4 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>

                    <input type="text" name="q" value="<?= h($search) ?>"
                           placeholder="Search products..."
                           class="w-full pl-11 pr-24 py-2.5 rounded-full border border-pink-200 bg-pink-50/60 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:bg-white text-sm">

                    <?php if ($categoryId): ?><input type="hidden" name="category_id" value="<?= (int)$categoryId ?>"><?php endif; ?>
                    <?php if ($brandId): ?><input type="hidden" name="brand_id" value="<?= (int)$brandId ?>"><?php endif; ?>

                    <div class="absolute right-2 flex items-center gap-1">
                        <!-- IMAGE SEARCH -->
                        <button type="button" onclick="document.getElementById('imageSearchInput').click()"
                                title="Search by image"
                                class="p-2 rounded-full text-gray-400 hover:text-pink-600 hover:bg-pink-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <input type="file" id="imageSearchInput" name="image" accept="image/*" class="hidden" onchange="document.getElementById('imageSearchForm').submit()">

                        <button type="submit"
                                class="px-4 py-1.5 rounded-full bg-pink-600 text-white text-sm font-medium hover:bg-pink-700 transition">
                            Search
                        </button>
                    </div>
                </div>
            </form>

            <div class="flex items-center gap-4 shrink-0">
                <!-- AVATAR MENU -->
                <div class="relative" id="avatarMenu">
                    <button id="avatarBtn" onclick="toggleAvatarMenu()"
                            class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-full hover:bg-pink-50 transition focus:outline-none">
                        <span class="w-9 h-9 rounded-full bg-pink-600 text-white flex items-center justify-center text-sm font-bold">
                            <?= h($initials ?: 'U') ?>
                        </span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="avatarDropdown"
                         class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-pink-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-pink-50">
                            <p class="font-semibold text-gray-800 truncate"><?= h($_SESSION['name']) ?></p>
                            <p class="text-sm text-gray-500 truncate"><?= h($_SESSION['email']) ?></p>
                        </div>
                        <div class="py-2">
                            <a href="#" class="flex items-center gap-3 px-5 py-2.5 text-sm text-gray-700 hover:bg-pink-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                My Profile
                            </a>
                            <a href="#" class="flex items-center gap-3 px-5 py-2.5 text-sm text-gray-700 hover:bg-pink-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                My Orders
                            </a>
                            <a href="#" class="flex items-center gap-3 px-5 py-2.5 text-sm text-gray-700 hover:bg-pink-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Settings
                            </a>
                        </div>
                        <div class="border-t border-pink-50 py-2">
                            <a href="../logout.php" class="flex items-center gap-3 px-5 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>

<!-- Hidden form used purely to submit the chosen image file alongside current filters -->
<form id="imageSearchForm" method="GET" action="" class="hidden">
    <input type="hidden" name="category_id" value="<?= (int)$categoryId ?>">
    <input type="hidden" name="brand_id" value="<?= (int)$brandId ?>">
</form>

<div class="max-w-7xl mx-auto px-6 py-10">

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">Overview of your account and the latest products.</p>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-10">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-pink-100">
        <p class="text-gray-500 text-sm">Account Email</p>
        <p class="text-lg font-bold text-gray-800 mt-1 truncate"><?= h($_SESSION['email']) ?></p>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-pink-100">
        <p class="text-gray-500 text-sm">Categories Available</p>
        <p class="text-lg font-bold text-gray-800 mt-1"><?= $totalCategories ?></p>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-pink-100">
        <p class="text-gray-500 text-sm">Member Since</p>
        <p class="text-lg font-bold text-gray-800 mt-1">
            <?= $memberSince ? h(date('M Y', strtotime($memberSince['created_at']))) : '-' ?>
        </p>
    </div>
</div>

<div class="grid lg:grid-cols-4 gap-8">

<!-- SIDEBAR FILTERS -->
<aside class="lg:col-span-1">
    <div class="bg-white rounded-2xl shadow-sm border border-pink-100 p-6 sticky top-28">

        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">Filters</h3>
            <?php if ($search !== '' || $categoryId || $brandId): ?>
            <a href="dashboard.php" class="text-xs text-pink-600 hover:underline font-medium">Clear all</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="" id="filterForm">
            <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= h($search) ?>"><?php endif; ?>

            <div class="mb-6">
                <p class="text-sm font-semibold text-gray-700 mb-3">Category</p>
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="radio" name="category_id" value="" onchange="this.form.submit()" <?= $categoryId === null ? 'checked' : '' ?>
                               class="text-pink-600 focus:ring-pink-500">
                        All Categories
                    </label>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="radio" name="category_id" value="<?= (int)$cat['id'] ?>" onchange="this.form.submit()"
                               <?= $categoryId === (int)$cat['id'] ? 'checked' : '' ?>
                               class="text-pink-600 focus:ring-pink-500">
                        <?= h($cat['category_name']) ?>
                    </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-700 mb-3">Brand</p>
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="radio" name="brand_id" value="" onchange="this.form.submit()" <?= $brandId === null ? 'checked' : '' ?>
                               class="text-pink-600 focus:ring-pink-500">
                        All Brands
                    </label>
                    <?php if ($brands && mysqli_num_rows($brands) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($brands)): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="radio" name="brand_id" value="<?= (int)$b['id'] ?>" onchange="this.form.submit()"
                                   <?= $brandId === (int)$b['id'] ? 'checked' : '' ?>
                                   class="text-pink-600 focus:ring-pink-500">
                            <?= h($b['brand_name']) ?>
                        </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-xs text-gray-400">No brands available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</aside>

<!-- PRODUCT GRID -->
<div class="lg:col-span-3">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">
            <?= $search !== '' ? 'Results for "' . h($search) . '"' : 'Browse Products' ?>
        </h2>
        <span class="text-sm text-gray-500"><?= mysqli_num_rows($products) ?> item<?= mysqli_num_rows($products) === 1 ? '' : 's' ?></span>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if ($products && mysqli_num_rows($products) > 0): ?>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-pink-100 hover:shadow-md transition">
            <img src="<?= h($p['image'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30') ?>" class="h-48 w-full object-cover">
            <div class="p-5">
                <p class="text-xs text-pink-500 font-medium"><?= h($p['category_name'] ?? '') ?></p>
                <h3 class="font-bold text-lg mt-1"><?= h($p['product_name']) ?></h3>
                <p class="text-xs text-gray-400 mt-0.5"><?= h($p['brand_name'] ?? '') ?></p>
                <p class="text-pink-600 font-bold text-xl mt-2">$<?= number_format((float)$p['price'], 2) ?></p>
                <button class="w-full mt-4 bg-pink-600 text-white py-2.5 rounded-xl hover:bg-pink-700 text-sm font-medium">Add To Cart</button>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-gray-500 col-span-full">No products match your filters.</p>
    <?php endif; ?>
    </div>

</div>

</div>

</div>

<script>
function toggleAvatarMenu() {
    document.getElementById('avatarDropdown').classList.toggle('hidden');
}

document.addEventListener('click', function (e) {
    const menu = document.getElementById('avatarMenu');
    const dropdown = document.getElementById('avatarDropdown');
    if (!menu.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>

</body>
</html>