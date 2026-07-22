<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Ecommerce%20website');
}
$pageTitle = 'Payment Failed';
$activePage = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16 animate-fade-in">
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-8 text-center card-hover">
        <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Payment Failed</h1>
        <p class="text-slate-600 mb-6">Your payment could not be processed. Please try again.</p>
        <div class="mt-6">
            <a href="<?= BASE_URL ?>/cart/index.php" class="inline-flex px-6 py-3 bg-indigo-600 hover:bg-indigo-600 text-white rounded-xl font-medium transition shadow-md">
                Retry Checkout
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="inline-flex ml-3 px-6 py-3 border border-indigo-200 text-slate-700 rounded-xl font-medium hover:bg-indigo-50 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
