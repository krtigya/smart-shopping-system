<?php
/**
 * Image Search (UI only for now / CNN to be wired by you).
 *
 * This endpoint no longer calls the Python engine so the page never hangs.
 * It just receives the uploaded photo, stores it, and shows a guide panel
 * describing exactly where/how to plug in your CNN.
 */
require_once __DIR__ . '/../config/connection.php';

$uploadedPath = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['query_image'])) {
    $file = $_FILES['query_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed, true)) {
            $uploadDir = __DIR__ . '/../ml/uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $savedName = 'q_' . session_id() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $savedName)) {
                $uploadedPath = 'ml/uploads/' . $savedName;
            } else {
                $error = 'Failed to save uploaded image.';
            }
        } else {
            $error = 'Unsupported file type. Use JPG, PNG or WEBP.';
        }
    } else {
        $error = 'Upload error code: ' . $file['error'];
    }
}

$pageTitle = 'Visual Search';
$activePage = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 animate-fade-in">
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 mb-6 px-5 py-2.5 rounded-xl bg-white border border-indigo-200 text-slate-700 text-sm font-medium shadow-sm hover:bg-indigo-50 hover:text-indigo-600 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Continue Shopping
    </a>

    <h1 class="text-3xl font-bold mb-2 text-slate-900">Visual Search</h1>
    <p class="text-slate-500 mb-8">Upload a product photo and find visually similar items (CNN backend to be connected).</p>

    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-5 mb-8"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($uploadedPath): ?>
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6 card-hover">
            <div class="flex flex-col sm:flex-row gap-6">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($uploadedPath) ?>" alt="Your upload" class="w-40 h-40 object-cover rounded-xl border border-indigo-200">
                <div class="flex-1">
                    <h2 class="font-semibold text-slate-900 mb-2">Upload received</h2>
                    <p class="text-sm text-slate-600 mb-4">Connect your CNN to return matches. Suggested flow:</p>
                    <ol class="text-sm text-slate-600 list-decimal pl-5 space-y-1">
                        <li>Extract a feature vector from the uploaded image (e.g. MobileNetV2 bottleneck).</li>
                        <li>Compare against feature vectors of <code>assets/images/*</code> via cosine similarity.</li>
                        <li>Return the top matches as <code>{"path","score"}</code> JSON.</li>
                        <li>Read the JSON here and query <code>products</code> / <code>product_images</code> to render results.</li>
                    </ol>
                    <p class="text-xs text-slate-400 mt-3">Saved at: <code><?= htmlspecialchars($uploadedPath) ?></code></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6 text-sm text-slate-600">
            No image uploaded yet. Use the camera button in the search bar to try it.
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
mysqli_close($conn);
?>
