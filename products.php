<?php
$pageTitle = 'Page Not Found';
$activePage = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center relative bg-indigo-50 py-20">
    <div class="relative bg-white rounded-2xl border border-indigo-200 shadow-xl max-w-lg w-full mx-4 p-8 text-center card-hover">
        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
            <span class="text-white font-bold text-2xl">AC</span>
        </div>
        <h1 class="text-5xl font-bold text-slate-900 mb-3">404</h1>
        <p class="text-slate-600 mb-6">The page you're looking for doesn't exist or has been moved.</p>

        <div class="flex justify-center gap-3 mb-8">
            <a href="<?= BASE_URL ?>/index.php" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition shadow-md hover:shadow-lg">
                Go Home
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="px-5 py-2.5 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">
                View Products
            </a>
        </div>

        <div class="relative mb-6">
            <input type="text" id="search-notfound" placeholder="Search products..."
                   class="w-full pl-4 pr-10 py-2.5 border border-indigo-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
            <button type="button" onclick="searchRedirect()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-indigo-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.601Z" />
                </svg>
            </button>
        </div>

        <p class="text-xs text-slate-400">&copy; <?= date('Y') ?> Smart Shopping System. All rights reserved.</p>
    </div>
</div>

<script>
    function searchRedirect() {
        const query = encodeURIComponent(document.getElementById('search-notfound').value);
        if(query) window.location.href = '<?= BASE_URL ?>/index.php?query=' + query;
    }
    document.getElementById('search-notfound').addEventListener('keypress', e => { if(e.key==='Enter') searchRedirect() });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
