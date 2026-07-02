<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$counts = [];
foreach (['users', 'products', 'categories', 'brands'] as $t) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$t`");
    $counts[$t] = $res ? (int)mysqli_fetch_assoc($res)['c'] : 0;
}

$recentUsers = mysqli_query($conn, "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 8");
$recentProducts = mysqli_query($conn, "SELECT p.*, c.category_name, b.brand_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    ORDER BY p.created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - SmartShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-pink-50">

<div class="flex min-h-screen">

<!-- SIDEBAR -->
<aside class="w-64 bg-pink-50 border-r border-pink-100 text-gray-700 hidden md:flex flex-col">
    <div class="px-6 py-6 text-2xl font-extrabold text-pink-700 border-b border-pink-100">SmartShop</div>
    <nav class="flex-1 px-4 py-6 space-y-1 text-sm">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-pink-600 text-white font-medium">Dashboard</a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-pink-100 transition">Products</a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-pink-100 transition">Categories</a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-pink-100 transition">Brands</a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-pink-100 transition">Customers</a>
    </nav>
    <div class="px-4 py-6 border-t border-pink-100">
        <a href="../logout.php" class="block text-center px-4 py-3 rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-medium">Logout</a>
    </div>
</aside>

<!-- MAIN -->
<main class="flex-1">

<header class="bg-white border-b border-pink-100 px-8 py-5 flex justify-between items-center">
    <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
    <div class="flex items-center gap-3">
        <span class="text-gray-600 text-sm">Hi, <span class="font-semibold text-gray-800"><?= h($_SESSION['name']) ?></span></span>
        <a href="../logout.php" class="md:hidden px-3 py-2 rounded-lg bg-pink-600 text-white text-xs">Logout</a>
    </div>
</header>

<div class="p-8">

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white rounded-2xl p-6 shadow">
        <p class="text-gray-500 text-sm">Total Users</p>
        <p class="text-3xl font-black text-pink-600 mt-2"><?= $counts['users'] ?></p>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow">
        <p class="text-gray-500 text-sm">Total Products</p>
        <p class="text-3xl font-black text-pink-600 mt-2"><?= $counts['products'] ?></p>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow">
        <p class="text-gray-500 text-sm">Categories</p>
        <p class="text-3xl font-black text-pink-600 mt-2"><?= $counts['categories'] ?></p>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow">
        <p class="text-gray-500 text-sm">Brands</p>
        <p class="text-3xl font-black text-pink-600 mt-2"><?= $counts['brands'] ?></p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-8">

<div class="bg-white rounded-2xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-pink-50 font-bold text-gray-800">Recent Users</div>
    <table class="w-full text-sm">
        <thead class="bg-pink-50 text-gray-500 text-left">
            <tr>
                <th class="px-6 py-3">Name</th>
                <th class="px-6 py-3">Email</th>
                <th class="px-6 py-3">Role</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-pink-50">
        <?php if ($recentUsers && mysqli_num_rows($recentUsers) > 0): ?>
            <?php while ($u = mysqli_fetch_assoc($recentUsers)): ?>
            <tr>
                <td class="px-6 py-3 font-medium text-gray-800"><?= h($u['name']) ?></td>
                <td class="px-6 py-3 text-gray-500"><?= h($u['email']) ?></td>
                <td class="px-6 py-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-pink-100 text-pink-700' ?>">
                        <?= h($u['role']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td class="px-6 py-4 text-gray-400" colspan="3">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-pink-50 font-bold text-gray-800">Recent Products</div>
    <table class="w-full text-sm">
        <thead class="bg-pink-50 text-gray-500 text-left">
            <tr>
                <th class="px-6 py-3">Product</th>
                <th class="px-6 py-3">Category</th>
                <th class="px-6 py-3">Price</th>
                <th class="px-6 py-3">Stock</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-pink-50">
        <?php if ($recentProducts && mysqli_num_rows($recentProducts) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($recentProducts)): ?>
            <tr>
                <td class="px-6 py-3 font-medium text-gray-800"><?= h($p['product_name']) ?></td>
                <td class="px-6 py-3 text-gray-500"><?= h($p['category_name'] ?? '-') ?></td>
                <td class="px-6 py-3 text-pink-600 font-semibold">$<?= number_format((float)$p['price'], 2) ?></td>
                <td class="px-6 py-3 text-gray-500"><?= (int)$p['stock'] ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td class="px-6 py-4 text-gray-400" colspan="4">No products found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

</div>
</main>
</div>

</body>
</html>