<?php
require_once __DIR__ . '/includes/config.php';

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY created_at DESC LIMIT 4");
$products = mysqli_query($conn, "SELECT p.*, c.category_name, b.brand_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.status = 1 ORDER BY p.created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;}
.hero-bg{
    background:
    radial-gradient(circle at top left,#fbcfe8 0%,transparent 50%),
    radial-gradient(circle at top right,#fce7f3 0%,transparent 50%),
    linear-gradient(135deg,#fdf2f8,#fce7f3);
}
.floating{animation:float 5s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0px);}50%{transform:translateY(-15px);}}
.fade-up{animation:fadeUp 1s ease;}
@keyframes fadeUp{from{opacity:0;transform:translateY(40px);}to{opacity:1;transform:translateY(0);}}
.product-card:hover{transform:translateY(-10px);}
.glass{backdrop-filter:blur(20px);background:rgba(255,255,255,.08);}
</style>
</head>
<body class="bg-pink-50">

<nav class="fixed w-full z-50 bg-white/70 backdrop-blur-lg border-b border-pink-100">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center h-20">
            <a href="index.php" class="text-3xl font-extrabold text-pink-600">SmartShop</a>
            <div class="hidden md:flex gap-8 text-gray-700 font-medium">
                <a href="#" class="hover:text-pink-600">Home</a>
                <a href="#products" class="hover:text-pink-600">Products</a>
                <a href="#categories" class="hover:text-pink-600">Categories</a>
                <a href="#" class="hover:text-pink-600">Brands</a>
                <a href="#" class="hover:text-pink-600">Contact</a>
            </div>
            <div class="hidden md:flex gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php' ?>"
                       class="px-5 py-2 rounded-xl bg-pink-600 text-white hover:bg-pink-700 transition">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="px-5 py-2 rounded-xl border border-pink-300 text-pink-700 hover:bg-pink-600 hover:text-white hover:border-pink-600 transition">Login</a>
                    <a href="register.php" class="px-5 py-2 rounded-xl bg-pink-600 text-white hover:bg-pink-700 transition">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<section class="hero-bg min-h-screen flex items-center pt-20">
<div class="max-w-7xl mx-auto px-6 py-20">
<div class="grid lg:grid-cols-2 gap-14 items-center">
<div class="fade-up">
<span class="px-4 py-2 bg-pink-200/60 text-pink-700 rounded-full text-sm font-medium">AI Powered Shopping Experience</span>
<h1 class="text-5xl lg:text-7xl font-black text-gray-800 mt-6 leading-tight">
Shop Smarter with <span class="text-pink-500">AI Search</span>
</h1>
<p class="text-gray-600 text-xl mt-6 leading-relaxed">
Discover products instantly using image search, recommendations and enterprise-grade shopping experience.
</p>
<div class="flex flex-wrap gap-4 mt-8">
<a href="register.php" class="bg-pink-600 hover:bg-pink-700 px-8 py-4 rounded-xl text-white font-semibold">Get Started</a>
<a href="#products" class="border border-pink-300 text-pink-700 px-8 py-4 rounded-xl hover:bg-pink-600 hover:text-white hover:border-pink-600 transition">Explore Products</a>
</div>
</div>
<div class="floating">
<img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d" class="rounded-3xl shadow-2xl" alt="shopping">
</div>
</div>
</div>
</section>

<section class="py-20 bg-white">
<div class="max-w-7xl mx-auto px-6">
<div class="grid md:grid-cols-4 gap-8 text-center">
<div><h3 class="text-5xl font-black text-pink-600">50K+</h3><p class="mt-2 text-gray-600">Customers</p></div>
<div><h3 class="text-5xl font-black text-pink-600">10K+</h3><p class="mt-2 text-gray-600">Products</p></div>
<div><h3 class="text-5xl font-black text-pink-600">500+</h3><p class="mt-2 text-gray-600">Brands</p></div>
<div><h3 class="text-5xl font-black text-pink-600">99%</h3><p class="mt-2 text-gray-600">Satisfaction</p></div>
</div>
</div>
</section>

<section id="categories" class="py-24">
<div class="max-w-7xl mx-auto px-6">
<h2 class="text-4xl font-black text-center mb-14 text-pink-900">Shop by Category</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
<?php if ($categories && mysqli_num_rows($categories) > 0): ?>
    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
    <div class="bg-white p-8 rounded-3xl shadow hover:shadow-xl transition">
        <?php if (!empty($cat['category_image'])): ?>
        <img src="<?= h($cat['category_image']) ?>" class="h-16 w-16 object-cover rounded-xl mb-4">
        <?php endif; ?>
        <h3 class="font-bold text-2xl"><?= h($cat['category_name']) ?></h3>
        <p class="text-gray-500 mt-2">Explore now</p>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <p class="text-gray-500 col-span-4 text-center">No categories yet.</p>
<?php endif; ?>
</div>
</div>
</section>

<section id="products" class="py-24 bg-pink-100/40">
<div class="max-w-7xl mx-auto px-6">
<h2 class="text-4xl font-black text-center mb-14 text-pink-900">Trending Products</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
<?php if ($products && mysqli_num_rows($products) > 0): ?>
    <?php while ($p = mysqli_fetch_assoc($products)): ?>
    <div class="product-card bg-white rounded-3xl overflow-hidden shadow-lg transition duration-300">
        <img src="<?= h($p['image'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30') ?>" class="h-64 w-full object-cover">
        <div class="p-6">
            <h3 class="font-bold text-xl"><?= h($p['product_name']) ?></h3>
            <p class="text-gray-400 text-sm mt-1"><?= h($p['brand_name'] ?? '') ?></p>
            <p class="text-pink-600 font-bold text-2xl mt-2">$<?= number_format((float)$p['price'], 2) ?></p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
            <button class="w-full mt-4 bg-pink-600 text-white py-3 rounded-xl hover:bg-pink-700">Add To Cart</button>
            <?php else: ?>
            <a href="login.php" class="block text-center w-full mt-4 bg-pink-600 text-white py-3 rounded-xl hover:bg-pink-700">Login to Buy</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <p class="text-gray-500 col-span-4 text-center">No products yet.</p>
<?php endif; ?>
</div>
</div>
</section>

<section class="py-24 bg-pink-600">
<div class="max-w-4xl mx-auto text-center px-6">
<h2 class="text-5xl font-black text-white">Ready To Start Shopping?</h2>
<p class="text-pink-100 mt-6 text-xl">Join thousands of happy customers.</p>
<a href="register.php" class="inline-block mt-8 bg-white text-pink-600 px-8 py-4 rounded-xl font-bold">Create Account</a>
</div>
</section>

<footer class="bg-pink-100 text-gray-600 py-14">
<div class="max-w-7xl mx-auto px-6">
<div class="grid md:grid-cols-4 gap-10">
<div><h3 class="text-3xl font-black text-pink-700">SmartShop</h3></div>
<div><h4 class="font-bold text-gray-800 mb-4">Company</h4><ul class="space-y-2"><li>About</li><li>Careers</li><li>Contact</li></ul></div>
<div><h4 class="font-bold text-gray-800 mb-4">Support</h4><ul class="space-y-2"><li>Help Center</li><li>Terms</li><li>Privacy</li></ul></div>
<div><h4 class="font-bold text-gray-800 mb-4">Newsletter</h4><input type="email" placeholder="Email" class="w-full p-3 rounded-lg text-black border border-pink-200"></div>
</div>
<div class="border-t border-pink-200 mt-10 pt-6 text-center">© 2026 SmartShop. All rights reserved.</div>
</div>
</footer>

</body>
</html>