<?php
/**
 * Image Search connected to Python FastAPI CNN Engine
 */
require_once __DIR__ . '/../config/connection.php';

$uploadedPath = '';
$error = '';
$matchedProducts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['query_image'])) {
    $file = $_FILES['query_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed, true)) {
            $uploadDir = __DIR__ . '/../ml/uploads/';
            if (!is_dir($uploadDir)) { 
                mkdir($uploadDir, 0755, true); 
            }
            
            $savedName = 'q_' . session_id() . '_' . time() . '.' . $ext;
            $fullUploadPath = $uploadDir . $savedName;

            if (move_uploaded_file($file['tmp_name'], $fullUploadPath)) {
                $uploadedPath = 'ml/uploads/' . $savedName;

                // --- CALL PYTHON FASTAPI API ENDPOINT ---
                $apiUrl = 'http://127.0.0.1:8000/api/search';
                $payload = json_encode([
                    'image_path' => realpath($fullUploadPath),
                    'top_k' => 6
                ]);

                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($response && $httpCode === 200) {
                    $apiResult = json_decode($response, true);
                    
                    // Extract match product IDs if returned by FastAPI
                    if (isset($apiResult['matches']) && !empty($apiResult['matches'])) {
                        $productIds = array_column($apiResult['matches'], 'product_id');
                        
                        if (!empty($productIds)) {
                            $idList = implode(',', array_map('intval', $productIds));
                            $sql = "SELECT * FROM products WHERE id IN ($idList)";
                            $result = mysqli_query($conn, $sql);
                            
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $matchedProducts[] = $row;
                                }
                            }
                        }
                    }
                } else {
                    // Fallback notice if FastAPI microservice is offline or still processing
                    $error = "AI Engine process error or service offline. Showing upload status.";
                }

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

$pageTitle = 'Visual Search Results';
$activePage = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 animate-fade-in">
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 mb-6 px-5 py-2.5 rounded-xl bg-white border border-indigo-200 text-slate-700 text-sm font-medium shadow-sm hover:bg-indigo-50 hover:text-indigo-600 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Continue Shopping
    </a>

    <h1 class="text-3xl font-bold mb-2 text-slate-900">Visual Search</h1>
    <p class="text-slate-500 mb-8">Uploaded image features mapped via ResNet50 CNN model.</p>

    <?php if ($error): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($uploadedPath): ?>
        <!-- User Query Display Card -->
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6 mb-8">
            <div class="flex flex-col sm:flex-row gap-6 items-center">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($uploadedPath) ?>" alt="Your Query" class="w-32 h-32 object-cover rounded-xl border border-indigo-200 shadow-sm">
                <div class="flex-1 text-center sm:text-left">
                    <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full mb-2">Query Processed</span>
                    <h2 class="font-semibold text-slate-900 text-lg">Searching for Visual Matches</h2>
                    <p class="text-xs text-slate-400 mt-1">Image stored at: <code><?= htmlspecialchars($uploadedPath) ?></code></p>
                </div>
            </div>
        </div>

        <!-- Matching Products Grid -->
        <h3 class="text-xl font-bold text-slate-900 mb-6">Visually Similar Products</h3>
        
        <?php if (!empty($matchedProducts)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php foreach ($matchedProducts as $product): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition">
                        <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($product['image'] ?? 'no-image.svg') ?>" 
                             alt="<?= htmlspecialchars($product['title'] ?? 'Product') ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h4 class="font-semibold text-slate-900 text-base mb-1">
                                <?= htmlspecialchars($product['title'] ?? 'Product Item') ?>
                            </h4>
                            <p class="text-indigo-600 font-bold text-lg mb-3">
                                $<?= number_format($product['price'] ?? 0, 2) ?>
                            </p>
                            <a href="<?= BASE_URL ?>/products/view.php?id=<?= $product['id'] ?>" 
                               class="block text-center w-full py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                No matching product vectors found in the database catalog yet. Run the catalog vector indexer to generate feature embeddings for your inventory.
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6 text-sm text-slate-600">
            No image uploaded yet. Use the search bar image upload button to start visual search.
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
if (isset($conn)) {
    mysqli_close($conn);
}
?>