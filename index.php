<?php
require_once __DIR__ . '/config/connection.php';

$category_id = isset($_GET['category']) && is_numeric($_GET['category']) ? $_GET['category'] : null;
$brand_id = isset($_GET['brand']) && is_numeric($_GET['brand']) ? $_GET['brand'] : null;
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Prepare SQL query with filtering using parameterized queries
$sql = "SELECT p.id, p.name, p.price, p.description, c.name AS category, b.name AS brand, p.image 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        JOIN brands b ON p.brand_id = b.id 
        WHERE 1=1"; 

if ($category_id) {
    $sql .= " AND p.category_id = " . intval($category_id);
}
if ($brand_id) {
    $sql .= " AND p.brand_id = " . intval($brand_id);
}
if ($query) {
    $escaped_query = mysqli_real_escape_string($conn, $query); 
    $sql .= " AND (p.name LIKE '%$escaped_query%' OR c.name LIKE '%$escaped_query%' OR b.name LIKE '%$escaped_query%')";
}

$productsResult = mysqli_query($conn, $sql);

if (!$productsResult) {
    die("Error executing query: " . mysqli_error($conn));
}

// Fetch categories for sidebar
$categoriesResult = mysqli_query($conn, "SELECT * FROM categories");

// Fetch brands for dropdown
$brandsResult = mysqli_query($conn, "SELECT * FROM brands");

// Store data to avoid loop pointer collision
$categories = [];
while ($row = mysqli_fetch_assoc($categoriesResult)) {
    $categories[] = $row;
}

$brands = [];
while ($row = mysqli_fetch_assoc($brandsResult)) {
    $brands[] = $row;
}

$pageTitle = 'Home';
$activePage = 'home';
$searchQuery = $query;
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-r from-indigo-500 via-indigo-400 to-indigo-500 py-16 sm:py-24 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1.5 bg-white/10 text-white rounded-full text-xs font-semibold uppercase tracking-wider mb-4 border border-white/30">Premium Collection</span>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight mb-4 leading-tight">Smart Shopping System</h1>
        <p class="text-lg sm:text-xl text-indigo-50 max-w-2xl mx-auto font-light leading-relaxed">Discover carefully curated collections crafted for modern living. Elevate your lifestyle with premium quality and contemporary design.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white border-b border-indigo-200 py-4 sticky top-20 sm:top-20 z-30 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Category Chips -->
        <div class="mb-4 flex items-center space-x-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide">
            <a href="<?= BASE_URL ?>/index.php" class="px-4 py-2 rounded-full text-xs font-medium border whitespace-nowrap transition flex-shrink-0 <?= !$category_id ? 'bg-indigo-600 border-indigo-500 text-white shadow-sm' : 'bg-indigo-50 border-indigo-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-100' ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= htmlspecialchars($cat['id']); ?><?= $brand_id ? '&brand='.$brand_id : '' ?><?= $query ? '&query='.urlencode($query) : '' ?>" class="px-4 py-2 rounded-full text-xs font-medium border whitespace-nowrap transition flex-shrink-0 <?= $category_id == $cat['id'] ? 'bg-indigo-600 border-indigo-500 text-white shadow-sm' : 'bg-indigo-50 border-indigo-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-100' ?>">
                    <?= htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filter Controls -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-sm text-slate-500"><?= mysqli_num_rows($productsResult); ?> Products Found</p>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Brand Filter -->
                <select onchange="location = this.value;" class="flex-1 sm:flex-none appearance-none bg-white border border-indigo-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                    <option value="products.php<?= $category_id ? '?category='.$category_id : '' ?>">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="?brand=<?= $b['id'] ?><?= $category_id ? '&category='.$category_id : '' ?><?= $query ? '&query='.urlencode($query) : '' ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Clear Filters -->
                <?php if($category_id || $brand_id || $query): ?>
                    <a href="products.php" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition" title="Clear filters">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php if (mysqli_num_rows($productsResult) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php while ($product = mysqli_fetch_assoc($productsResult)): ?>
                <div class="group bg-white rounded-xl border border-indigo-100 overflow-hidden shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300 flex flex-col h-full hover:-translate-y-0.5 animate-fade-in">
                    
                    <!-- Product Image -->
                    <div class="relative bg-indigo-50 aspect-square overflow-hidden">
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg';">
                        <div class="absolute top-3 left-3">
                            <span class="inline-block bg-white/95 backdrop-blur text-slate-800 text-xs font-bold tracking-wider uppercase px-2.5 py-1 rounded-lg shadow-sm">
                                <?= htmlspecialchars($product['brand']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-5 flex flex-col flex-grow">
                        <span class="text-xs text-indigo-600 font-semibold uppercase tracking-wider mb-2 block"><?= htmlspecialchars($product['category']); ?></span>
                        <h3 class="font-semibold text-slate-800 group-hover:text-indigo-600 transition duration-150 line-clamp-2 text-sm mb-2" title="<?= htmlspecialchars($product['name']); ?>">
                            <?= htmlspecialchars($product['name']); ?>
                        </h3>
                        <p class="text-xs text-slate-500 line-clamp-2 mb-4 flex-grow leading-relaxed">
                            <?= htmlspecialchars($product['description']); ?>
                        </p>
                        
                        <!-- Price & Action -->
                        <div class="pt-4 border-t border-indigo-100 flex items-center justify-between gap-3 mt-auto">
                            <div>
                                <span class="text-xs text-slate-400 font-semibold uppercase tracking-tight block mb-0.5">Price</span>
                                <span class="text-xl font-bold text-slate-900">Rs. <?= htmlspecialchars($product['price']); ?></span>
                            </div>
                            <a href="<?= BASE_URL ?>/products/view.php?id=<?= htmlspecialchars($product['id']); ?>" class="inline-flex items-center justify-center bg-indigo-50 hover:bg-indigo-700 text-indigo-600 hover:text-white px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-24 bg-white rounded-2xl border border-dashed border-indigo-200 max-w-2xl mx-auto px-4 shadow-sm">
            <div class="p-4 bg-indigo-100 text-indigo-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.601Z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No Products Found</h3>
            <p class="text-sm text-slate-500 mb-6">We couldn't find any products matching your filters. Try adjusting your search criteria.</p>
            <a href="products.php" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-600 text-white font-semibold px-6 py-3 rounded-xl transition shadow-md hover:shadow-lg">View All Products</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
