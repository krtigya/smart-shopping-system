<?php
require_once __DIR__ . '/config/connection.php';

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name === '' || $email === '' || $message === '') {
        $error = 'Please fill in your name, email, and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $sent = true;
    }
}

$pageTitle = 'Contact';
$activePage = 'contact';
$searchQuery = '';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-700">Get in touch</p>
    <h1 class="mt-2 text-3xl font-bold text-slate-900">Contact us</h1>
    <p class="mt-2 text-slate-500">Questions about an order or the shop? Send us a message.</p>

    <?php if ($sent): ?>
        <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800">Thanks, <?= htmlspecialchars($name) ?>. We received your message and will get back to you at <?= htmlspecialchars($email) ?>.</div>
    <?php else: ?>
        <?php if ($error): ?><div class="mt-8 rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" class="mt-8 space-y-5 bg-white rounded-2xl border border-indigo-200 p-6 shadow-sm">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input name="name" required value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['first_name'] ?? '')) ?>" class="w-full rounded-xl border border-indigo-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['email'] ?? '')) ?>" class="w-full rounded-xl border border-indigo-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                <textarea name="message" rows="5" required class="w-full rounded-xl border border-indigo-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button class="px-6 py-2.5 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white font-semibold">Send message</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
