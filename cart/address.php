<?php
require_once __DIR__ . '/../config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

// Load existing address (if any) to prefill
$existing = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");
$addr = ($existing && $existing->num_rows > 0) ? $existing->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $province = mysqli_real_escape_string($conn, trim($_POST['province']));
    $city     = mysqli_real_escape_string($conn, trim($_POST['city']));
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));

    if ($addr) {
        $conn->query("UPDATE addresses SET province='$province', city='$city', location='$location' WHERE id = {$addr['id']}");
    } else {
        $conn->query("INSERT INTO addresses (user_id, province, city, location) VALUES ($user_id, '$province', '$city', '$location')");
    }

    if (isset($_GET['product_id'])) {
        header("Location: " . BASE_URL . "/cart/index.php?product_id=" . (int)$_GET['product_id']);
    } else {
        header("Location: " . BASE_URL . "/account/index.php");
    }
    exit();
}

$pageTitle = $addr ? 'Edit Address' : 'Add Address';
$activePage = '';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-lg mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-8">
        <h1 class="text-2xl font-bold text-slate-900"><?= $addr ? 'Edit' : 'Add' ?> Address</h1>
        <p class="text-sm text-slate-500 mt-1">Tell us where to deliver your order.</p>

        <form method="POST" action="<?= BASE_URL ?>/cart/address.php?product_id=<?= isset($_GET['product_id']) ? (int)$_GET['product_id'] : '' ?>" class="mt-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Province / State</label>
                <input name="province" value="<?= htmlspecialchars($addr['province'] ?? '') ?>" required
                    class="w-full border border-indigo-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                <input name="city" value="<?= htmlspecialchars($addr['city'] ?? '') ?>" required
                    class="w-full border border-indigo-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Detailed Location / Street</label>
                <textarea name="location" rows="3" required class="w-full border border-indigo-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($addr['location'] ?? '') ?></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <a href="<?= BASE_URL ?>/account/index.php" class="flex-1 px-5 py-2.5 rounded-xl border border-indigo-200 text-slate-700 text-sm font-medium text-center hover:bg-indigo-50 transition">Cancel</a>
                <button type="submit" class="flex-1 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-600 text-white text-sm font-semibold shadow transition">Save Address</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php mysqli_close($conn); ?>
