<?php
// Shared admin layout header.
// Expects: $pageTitle (string), $activePage (string) set by the calling page,
// and config/connection.php already required (so BASE_URL is defined).
if (!isset($pageTitle)) { $pageTitle = 'Admin'; }
if (!isset($activePage)) { $activePage = ''; }

$nav = [
    ['key' => 'dashboard', 'label' => 'Dashboard',      'href' => BASE_URL . '/admin/dashboard.php', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['key' => 'products',  'label' => 'Products',       'href' => BASE_URL . '/admin/products.php',  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ['key' => 'categories','label' => 'Categories',     'href' => BASE_URL . '/admin/categories.php','icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
    ['key' => 'brands',    'label' => 'Brands',         'href' => BASE_URL . '/admin/brands.php',    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
    ['key' => 'orders',    'label' => 'Orders',         'href' => BASE_URL . '/admin/orders.php',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ['key' => 'users',     'label' => 'Users',          'href' => BASE_URL . '/admin/users.php',    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> &middot; Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #818cf8; border-radius: 9999px; }
    </style>
</head>
<body class="bg-indigo-50 text-slate-800">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-indigo-900 text-indigo-100 flex flex-col fixed inset-y-0 left-0 z-30">
        <div class="h-16 flex items-center gap-3 px-6 border-b border-indigo-800">
            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-indigo-500 rounded-lg flex items-center justify-center shadow">
                <span class="text-white font-bold text-xs">AC</span>
            </div>
            <span class="text-white font-semibold tracking-tight text-sm leading-tight">Smart Shopping System Admin</span>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
            <?php foreach ($nav as $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                   <?= $activePage === $item['key'] ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
                </svg>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-indigo-800">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-indigo-200 hover:bg-indigo-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 ml-64 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-indigo-200 flex items-center justify-between px-6 sticky top-0 z-20">
            <h1 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($pageTitle) ?></h1>
            <span class="text-sm text-indigo-600">Admin Panel</span>
        </header>
        <main class="flex-1 p-6">
<?php
