<?php
require_once __DIR__ . '/../config/connection.php';

$editId    = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$category  = $editId ? $conn->query("SELECT * FROM categories WHERE id = $editId")->fetch_assoc() : null;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name === '') {
        $message = 'Category name is required.';
    } else {
        $nameE = $conn->real_escape_string($name);
        $descE = $conn->real_escape_string($desc);
        if ($category) {
            $conn->query("UPDATE categories SET name='$nameE', description='$descE', status=$status WHERE id=" . $category['id']);
        } else {
            $conn->query("INSERT INTO categories (name, description, status) VALUES ('$nameE', '$descE', $status)");
        }
        header('Location: ' . BASE_URL . '/admin/categories.php');
        exit;
    }
}

$pageTitle  = $category ? 'Edit Category' : 'Add Category';
$activePage = 'categories';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($message): ?>
    <div class="mb-5 p-4 rounded-lg bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-2xl space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Category Name</label>
        <input name="name" required value="<?= htmlspecialchars($category['name'] ?? ($_POST['name'] ?? '')) ?>"
            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="4" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($category['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
    </div>
    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="status" value="1" <?= (!isset($category) || ($category['status'] ?? 1)) ? 'checked' : '' ?> class="rounded border-slate-300 text-indigo-600">
        Active
    </label>
    <div class="flex justify-end gap-3 pt-2">
        <a href="<?= BASE_URL ?>/admin/categories.php" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Cancel</a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-600 text-white text-sm font-semibold shadow">Save Category</button>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/admin_footer.php';
