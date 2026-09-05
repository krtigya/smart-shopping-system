<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/ImageSearchClient.php';
require_once __DIR__ . '/../includes/visual_search_helpers.php';

$isAjax = isset($_GET['ajax']);
$redirectUrl = BASE_URL . '/index.php?catalog=1&visual=1';
$error = '';
$uploadedPath = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['query_image'])) {
    $file = $_FILES['query_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error code: ' . $file['error'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed, true)) {
            $error = 'Unsupported file type. Use JPG, PNG or WEBP.';
        } else {
            $uploadDir = __DIR__ . '/../ml/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $savedName = 'q_' . session_id() . '_' . time() . '.' . $ext;
            $absolutePath = $uploadDir . $savedName;

            if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
                $error = 'Failed to save uploaded image.';
            } else {
                $uploadedPath = 'ml/uploads/' . $savedName;

                try {
                    $client = new ImageSearchClient();
                    if (!$client->isHealthy()) {
                        throw new RuntimeException(
                            'Visual search API is not running. Start it with: cd api && ./start.sh'
                        );
                    }

                    $matches = $client->search($absolutePath, 12);
                    $productScores = visual_search_map_matches_to_products($conn, $matches);

                    if (!$productScores) {
                        $error = 'No matching products found for this image.';
                    } else {
                        visual_search_store_session($productScores, $uploadedPath);
                        $results = visual_search_fetch_products($conn, $productScores, 12);
                    }
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'redirect' => $error === '' ? $redirectUrl : null,
            'error' => $error !== '' ? $error : null,
            'results' => $results,
        ]);
        mysqli_close($conn);
        exit;
    }

    if ($error === '') {
        header('Location: ' . $redirectUrl);
        mysqli_close($conn);
        exit;
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
    <p class="text-slate-500 mb-8">Upload a product photo to find visually similar items.</p>

    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-5 mb-8"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/products/image_search.php" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6 mb-8">
        <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-indigo-300 rounded-xl py-10 cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition">
            <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
            <span class="text-sm font-medium text-slate-700">Choose an image</span>
            <input type="file" name="query_image" accept="image/jpeg,image/png,image/webp" class="hidden" required>
        </label>
        <button type="submit" class="mt-4 w-full rounded-xl bg-pink-500 px-4 py-3 text-sm font-semibold text-white hover:bg-pink-600 transition">Search similar products</button>
    </form>

    <?php if ($uploadedPath && $results): ?>
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Top matches</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($results as $product): ?>
                    <a href="<?= BASE_URL ?>/products/view.php?id=<?= (int)$product['product_id'] ?>" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-pink-300 hover:bg-pink-50 transition">
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image']) ?>" alt="" class="h-16 w-16 rounded-lg object-cover">
                        <span>
                            <strong class="block text-sm text-slate-800"><?= htmlspecialchars($product['name']) ?></strong>
                            <small class="text-xs text-slate-500">Rs. <?= number_format((float)$product['price'], 2) ?> · <?= number_format((float)$product['score'] * 100, 1) ?>% match</small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
mysqli_close($conn);
?>
