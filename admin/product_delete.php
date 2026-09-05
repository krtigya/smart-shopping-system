<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if (isset($_POST['productId']) && is_numeric($_POST['productId'])) {
    $productId = (int)$_POST['productId'];

    // Remove physical image files first
    $res = $conn->query("SELECT image FROM product_images WHERE product_id = $productId");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $file = IMAGES_DIR . DIRECTORY_SEPARATOR . basename($row['image']);
            if (file_exists($file)) { @unlink($file); }
        }
    }

    // product_images / product_variants are removed automatically via FOREIGN KEY ON DELETE CASCADE
    $conn->query("DELETE FROM products WHERE id = $productId");
    require_once __DIR__ . '/../includes/rebuild_visual_index.php';
    rebuild_visual_index();
}

header('Location: ' . BASE_URL . '/admin/products.php');
exit;
