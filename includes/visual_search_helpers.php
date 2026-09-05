<?php

/**
 * @return array<int, int>
 */
function visual_search_find_product_ids(mysqli $conn, string $imageName): array
{
    $imageName = basename($imageName);
    if ($imageName === '') {
        return [];
    }

    $like = '%' . $imageName;
    $ids = [];

    $stmt = $conn->prepare('SELECT p.id FROM products p WHERE p.status = 1 AND p.image LIKE ?');
    if ($stmt) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $ids[] = (int)$row['id'];
        }
        $stmt->close();
    }

    $stmt = $conn->prepare(
        'SELECT pi.product_id AS id
         FROM product_images pi
         JOIN products p ON p.id = pi.product_id AND p.status = 1
         WHERE pi.image LIKE ?'
    );
    if (!$stmt) {
        return array_values(array_unique($ids));
    }

    $stmt->bind_param('s', $like);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $ids[] = (int)$row['id'];
    }
    $stmt->close();

    return array_values(array_unique($ids));
}

/**
 * @param array<int, array{image_name?: string, score?: float}> $matches
 * @return array<int, float> product_id => similarity score
 */
function visual_search_map_matches_to_products(mysqli $conn, array $matches): array
{
    $byProductId = [];

    foreach ($matches as $match) {
        $imageName = (string)($match['image_name'] ?? '');
        $score = (float)($match['score'] ?? 0);
        foreach (visual_search_find_product_ids($conn, $imageName) as $productId) {
            if (!isset($byProductId[$productId]) || $score > $byProductId[$productId]) {
                $byProductId[$productId] = $score;
            }
        }
    }

    arsort($byProductId);

    return $byProductId;
}

/**
 * @param array<int, float> $productScores
 */
function visual_search_store_session(array $productScores, string $relativeImagePath): void
{
    $_SESSION['visual_search_ids'] = array_keys($productScores);
    $_SESSION['visual_search_scores'] = $productScores;
    $_SESSION['visual_search_image'] = $relativeImagePath;
}

/**
 * @param array<int, float> $productScores
 * @return array<int, array<string, mixed>>
 */
function visual_search_fetch_products(mysqli $conn, array $productScores, int $limit = 12): array
{
    $ids = array_slice(array_keys($productScores), 0, $limit);
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.id, p.name, p.price, p.image
            FROM products p
            WHERE p.status = 1 AND p.id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $byId = [];
    foreach ($rows as $row) {
        $byId[(int)$row['id']] = $row;
    }

    $results = [];
    foreach ($ids as $id) {
        if (!isset($byId[$id])) {
            continue;
        }
        $results[] = [
            'product_id' => $id,
            'name' => $byId[$id]['name'],
            'price' => (float)$byId[$id]['price'],
            'image' => $byId[$id]['image'],
            'score' => $productScores[$id] ?? 0,
        ];
    }

    return $results;
}
