-- Replace products outside the trained visual-search taxonomy and seed the
-- missing trained categories with products, images, and three variants each.
START TRANSACTION;

-- Products in unsupported categories were already made NULL by the category
-- cleanup. ON DELETE CASCADE removes their variants and product images.
DELETE FROM products WHERE category_id IS NULL;

INSERT INTO products (name, description, category_id, brand_id, price, quantity, image, status)
SELECT seed.name, seed.description, c.id, NULL, seed.price, 30, seed.image, 1
FROM (
    SELECT 'Everyday Backpack' name, 'Practical everyday backpack' description, 'Backpacks' category, 4499.00 price, 'assets/images/crossbody.jpg' image
    UNION ALL SELECT 'Classic Briefs', 'Comfortable everyday briefs', 'Briefs', 899.00, 'assets/images/linen_set.jpg'
    UNION ALL SELECT 'Everyday Casual Shoes', 'Comfortable casual shoes', 'Casual Shoes', 3999.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Comfort Flip Flops', 'Lightweight flip flops', 'Flip Flops', 999.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Classic Formal Shoes', 'Polished formal shoes', 'Formal Shoes', 5499.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Structured Handbag', 'A versatile structured handbag', 'Handbags', 5999.00, 'assets/images/crossbody.jpg'
    UNION ALL SELECT 'Classic Block Heels', 'Comfortable block heels', 'Heels', 4799.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Straight Fit Jeans', 'Classic straight fit jeans', 'Jeans', 3499.00, 'assets/images/linen_set.jpg'
    UNION ALL SELECT 'Cotton Kurta', 'Comfortable cotton kurta', 'Kurtas', 2999.00, 'assets/images/linen_set.jpg'
    UNION ALL SELECT 'Fresh Body Mist', 'Light everyday body mist', 'Perfume and Body Mist', 1599.00, 'assets/images/wallet.jpg'
    UNION ALL SELECT 'Everyday Sandals', 'Comfortable open sandals', 'Sandals', 1999.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Classic Button Shirt', 'Versatile button-down shirt', 'Shirts', 2999.00, 'assets/images/linen_set.jpg'
    UNION ALL SELECT 'Cotton Ankle Socks', 'Soft cotton ankle socks', 'Socks', 499.00, 'assets/images/belt.jpg'
    UNION ALL SELECT 'Active Sports Shoes', 'Lightweight athletic shoes', 'Sports Shoes', 4999.00, 'assets/images/heels.jpg'
    UNION ALL SELECT 'Essential Cotton Tshirt', 'Soft everyday cotton T-shirt', 'Tshirts', 1499.00, 'assets/images/linen_set.jpg'
    UNION ALL SELECT 'Classic Wrist Watch', 'Minimal everyday wrist watch', 'Watches', 6999.00, 'assets/images/wallet.jpg'
) AS seed
JOIN categories c ON c.name = seed.category
LEFT JOIN products existing ON existing.name = seed.name
WHERE existing.id IS NULL;

-- Ensure every seeded product has a catalog image record.
INSERT INTO product_images (product_id, image, description, sort_order)
SELECT p.id, p.image, 'Main product image', 0
FROM products p
WHERE p.name IN (
    'Everyday Backpack', 'Classic Briefs', 'Everyday Casual Shoes',
    'Comfort Flip Flops', 'Classic Formal Shoes', 'Structured Handbag',
    'Classic Block Heels', 'Straight Fit Jeans', 'Cotton Kurta',
    'Fresh Body Mist', 'Everyday Sandals', 'Classic Button Shirt',
    'Cotton Ankle Socks', 'Active Sports Shoes', 'Essential Cotton Tshirt',
    'Classic Wrist Watch'
)
AND NOT EXISTS (
    SELECT 1 FROM product_images pi WHERE pi.product_id = p.id
);

-- Three size/options variants per seeded product.
INSERT INTO product_variants (product_id, size, uom, sku, price, quantity)
SELECT p.id, options.size, 'Piece', CONCAT('TRAIN-', LPAD(p.id, 3, '0'), '-', options.code),
       p.price + options.delta, 10
FROM products p
JOIN (
    SELECT 'Small' size, 'S' code, 0.00 delta
    UNION ALL SELECT 'Medium', 'M', 150.00
    UNION ALL SELECT 'Large', 'L', 300.00
) AS options
WHERE p.name IN (
    'Everyday Backpack', 'Classic Briefs', 'Everyday Casual Shoes',
    'Comfort Flip Flops', 'Classic Formal Shoes', 'Structured Handbag',
    'Classic Block Heels', 'Straight Fit Jeans', 'Cotton Kurta',
    'Fresh Body Mist', 'Everyday Sandals', 'Classic Button Shirt',
    'Cotton Ankle Socks', 'Active Sports Shoes', 'Essential Cotton Tshirt',
    'Classic Wrist Watch'
)
AND NOT EXISTS (
    SELECT 1 FROM product_variants pv
    WHERE pv.sku = CONCAT('TRAIN-', LPAD(p.id, 3, '0'), '-', options.code)
);

COMMIT;
