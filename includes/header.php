<?php
$pageTitle = $pageTitle ?? 'Smart Shopping System';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> - Smart Shopping System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .bg-indigo-gradient {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 50%, #4f46e5 100%);
        }
        .bg-indigo-soft {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        }
        .btn-indigo {
            background: linear-gradient(135deg, #4f46e5 0%, #4f46e5 100%);
            transition: all 0.3s ease;
        }
        .btn-indigo:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-slide-in {
            animation: slideIn 0.4s ease-out;
        }
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #4f46e5;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        .dropdown-menu {
            transform-origin: top right;
            transition: all 0.2s ease;
        }
        .mobile-menu {
            transition: max-height 0.3s ease, opacity 0.3s ease;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-indigo-soft min-h-screen text-slate-800">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-indigo-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300 group-hover:scale-110">
                            <span class="text-white font-bold text-lg">AC</span>
                        </div>
                        <span class="text-xl font-bold text-slate-900 hidden sm:inline group-hover:text-indigo-600 transition-colors">Smart Shopping System</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="<?= BASE_URL ?>/index.php" class="nav-link text-sm font-medium text-slate-600 hover:text-indigo-600 <?= $activePage === 'home' ? 'active text-indigo-600' : '' ?>">Home</a>
                    <a href="<?= BASE_URL ?>/index.php" class="nav-link text-sm font-medium text-slate-600 hover:text-indigo-600 <?= $activePage === 'products' ? 'active text-indigo-600' : '' ?>">Products</a>
                    <a href="<?= BASE_URL ?>/orders/index.php" class="nav-link text-sm font-medium text-slate-600 hover:text-indigo-600 <?= $activePage === 'orders' ? 'active text-indigo-600' : '' ?>">Orders</a>
                    <a href="contact.php" class="nav-link text-sm font-medium text-slate-600 hover:text-indigo-600">Contact</a>
                </div>

                <!-- Right Section -->
                <div class="flex items-center gap-4">
                    <!-- Search -->
                    <div class="hidden sm:flex relative">
                        <input type="text" id="search-query" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery ?? '') ?>"
                               class="w-64 bg-indigo-50 border border-indigo-200 rounded-xl pl-4 pr-20 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition placeholder:text-slate-400">
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                            <button onclick="openImageSearch()" class="p-1.5 text-slate-400 hover:text-indigo-600 transition" title="Search by image">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5a.75.75 0 0 0-.75.75v13.5c0 .414.336.75.75.75h3.697a.75.75 0 0 0 .53-.22l1.4-1.4a.75.75 0 0 1 .53-.22h5.646a.75.75 0 0 1 .53.22l1.4 1.4a.75.75 0 0 0 .53.22h2.033a.75.75 0 0 0 .75-.75V5.25a.75.75 0 0 0-.75-.75H3.75Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0Z"/>
                                </svg>
                            </button>
                            <button onclick="executeSearch(document.getElementById('search-query').value)" class="p-1.5 text-slate-400 hover:text-indigo-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.601Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Cart -->
                    <a href="<?= BASE_URL ?>/cart/index.php" class="relative p-2.5 rounded-xl text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                        </svg>
                        <span id="cart-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-300 text-white text-xs rounded-full flex items-center justify-center font-medium hidden">0</span>
                    </a>

                    <!-- User Menu -->
                    <div class="flex items-center gap-3">
                        <?php if (isset($_SESSION['username'])): ?>
                            <div class="relative">
                                <button id="avatarBtn" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-indigo-50 transition">
                                    <span class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-semibold text-sm shadow-sm">
                                        <?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'] ?? '', 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1))) ?>
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="avatarMenu" class="hidden absolute right-0 top-12 w-64 bg-white rounded-xl shadow-xl border border-indigo-200 py-2 z-50 dropdown-menu">
                                    <div class="px-4 py-3 border-b border-indigo-100">
                                        <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: $_SESSION['username']) ?></p>
                                        <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
                                    </div>
                                    <a href="<?= BASE_URL ?>/account/index.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 transition">My Account</a>
                                    <a href="<?= BASE_URL ?>/orders/index.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 transition">My Orders</a>
                                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 transition">Admin Panel</a>
                                    <?php endif; ?>
                                    <div class="border-t border-indigo-100 mt-1 pt-1">
                                        <a href="<?= BASE_URL ?>/auth/logout.php" class="block px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition">Logout</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/auth/login.php" class="hidden sm:inline-flex px-5 py-2.5 btn-indigo text-white rounded-xl font-medium text-sm shadow-md">Sign In</a>
                            <a href="<?= BASE_URL ?>/auth/register.php" class="hidden sm:inline-flex px-5 py-2.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-400 rounded-xl font-medium text-sm transition">Register</a>
                        <?php endif; ?>
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-xl hover:bg-indigo-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-slate-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-indigo-200 bg-white">
            <div class="px-4 py-4 space-y-3">
                <a href="<?= BASE_URL ?>/index.php" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-indigo-50 transition">Home</a>
                <a href="<?= BASE_URL ?>/index.php" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-indigo-50 transition">Products</a>
                <a href="<?= BASE_URL ?>/orders/index.php" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-indigo-50 transition">Orders</a>
                <a href="contact.php" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-indigo-50 transition">Contact</a>
                <?php if (!isset($_SESSION['username'])): ?>
                    <div class="border-t border-indigo-200 pt-3 mt-3 flex gap-3">
                        <a href="<?= BASE_URL ?>/auth/login.php" class="flex-1 px-5 py-3 btn-indigo text-white rounded-xl font-medium text-center text-sm">Sign In</a>
                        <a href="<?= BASE_URL ?>/auth/register.php" class="flex-1 px-5 py-3 bg-indigo-100 text-indigo-400 rounded-xl font-medium text-center text-sm">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Search -->
    <div class="sm:hidden bg-white border-b border-indigo-200 px-4 py-4 sticky top-20 z-30">
        <div class="relative">
            <input type="text" id="search-query-mobile" value="<?= htmlspecialchars($searchQuery ?? '') ?>" placeholder="Search products..." class="w-full bg-indigo-50 border border-indigo-200 rounded-xl pl-4 pr-20 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                <button type="button" title="Search by image" class="p-2 text-slate-400 hover:text-indigo-600" onclick="openImageSearch()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5a.75.75 0 0 0-.75.75v13.5c0 .414.336.75.75.75h3.697a.75.75 0 0 0 .53-.22l1.4-1.4a.75.75 0 0 1 .53-.22h5.646a.75.75 0 0 1 .53.22l1.4 1.4a.75.75 0 0 0 .53.22h2.033a.75.75 0 0 0 .75-.75V5.25a.75.75 0 0 0-.75-.75H3.75Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0Z"/>
                    </svg>
                </button>
                <button type="button" class="p-2 text-slate-400 hover:text-indigo-600" onclick="executeSearch(document.getElementById('search-query-mobile').value)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.601Z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow">