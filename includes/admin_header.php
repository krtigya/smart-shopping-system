<?php
// Shared admin layout header.
// Expects: $pageTitle (string), $activePage (string) set by the calling page,
// and config/connection.php already required (so BASE_URL is defined).
if (!isset($pageTitle)) { $pageTitle = 'Admin'; }
if (!isset($activePage)) { $activePage = ''; }
require_once __DIR__ . '/admin_auth.php';

$nav = [
    ['key' => 'dashboard', 'label' => 'Dashboard',      'href' => BASE_URL . '/admin/dashboard.php', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['key' => 'products',  'label' => 'Products',       'href' => BASE_URL . '/admin/products.php',  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ['key' => 'categories','label' => 'Categories',     'href' => BASE_URL . '/admin/categories.php','icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
    ['key' => 'brands',    'label' => 'Brands',         'href' => BASE_URL . '/admin/brands.php',    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
    ['key' => 'orders',    'label' => 'Orders',         'href' => BASE_URL . '/admin/orders.php',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ['key' => 'users',     'label' => 'Users',          'href' => BASE_URL . '/admin/users.php',    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
];

$adminInitials = strtoupper(
    substr($_SESSION['first_name'] ?? 'A', 0, 1) .
    substr($_SESSION['last_name'] ?? 'D', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> &middot; Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/enterprise-theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .admin-sidebar {
            background: #ffffff;
            border-right: 1px solid #fde8f0;
        }
        .admin-nav-active {
            background: #fff7fa;
            color: #df4f89;
            box-shadow: inset 3px 0 0 #f0659d;
        }
        .admin-nav-idle:hover {
            background: #fff7fa;
            color: #c93d76;
        }
        .admin-main-bg {
            background: linear-gradient(160deg, #fff7fa 0%, #ffffff 45%, #fde8f0 100%);
        }
        .scrollbar-thin::-webkit-scrollbar { width: 5px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #f3a3c0; border-radius: 9999px; }
    </style>
</head>
<body class="enterprise-theme admin-main-bg text-slate-800">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="admin-sidebar w-64 flex flex-col fixed inset-y-0 left-0 z-30">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-pink-100">
            <div class="w-9 h-9 bg-gradient-to-br from-pink-500 to-pink-700 rounded-xl flex items-center justify-center shadow-sm">
                <span class="text-white font-bold text-xs">AC</span>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold leading-tight truncate text-slate-900">Smart Shopping</p>
                <p class="text-[10px] uppercase tracking-widest text-pink-600">Admin Panel</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto scrollbar-thin">
            <?php foreach ($nav as $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                   <?= $activePage === $item['key']
                       ? 'admin-nav-active font-semibold'
                       : 'text-slate-600 admin-nav-idle' ?>">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
                    </svg>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-t border-pink-100 space-y-1">
            <?php /* View Store — hidden for admin sidebar
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 admin-nav-idle transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                View Store
            </a>
            */ ?>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 admin-nav-idle transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 ml-64 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-pink-100 flex items-center justify-between px-6 sticky top-0 z-20">
            <h1 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($pageTitle) ?></h1>
            <div class="flex items-center gap-3">
                <span class="hidden sm:block text-sm text-slate-500"><?= htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: ($_SESSION['username'] ?? 'Admin')) ?></span>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-pink-500 to-pink-700 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                    <?= htmlspecialchars($adminInitials) ?>
                </div>
            </div>
        </header>
        <main class="flex-1 p-6 lg:p-8">
