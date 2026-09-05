-- Apply the downloaded high-resolution online images to the seeded catalog.
UPDATE products
SET image = CONCAT('assets/images/online_product_', id, '.jpg')
WHERE id IN (1, 2, 3, 6, 13, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30);

UPDATE product_images pi
JOIN products p ON p.id = pi.product_id
SET pi.image = p.image
WHERE p.id IN (1, 2, 3, 6, 13, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30);
