-- Keep the store taxonomy aligned with the trained visual-search classes.
-- Product rows are preserved; unsupported category references become NULL
-- through the existing products.category_id ON DELETE SET NULL constraint.

INSERT IGNORE INTO categories (name, description, status) VALUES
('Backpacks', 'Backpacks and daypacks', 1),
('Belts', 'Belts and waist accessories', 1),
('Briefs', 'Briefs and underwear', 1),
('Casual Shoes', 'Casual footwear', 1),
('Flip Flops', 'Flip flops and sandals', 1),
('Formal Shoes', 'Formal footwear', 1),
('Handbags', 'Handbags and purses', 1),
('Heels', 'Heeled footwear', 1),
('Jeans', 'Denim jeans', 1),
('Kurtas', 'Kurtas and traditional tops', 1),
('Perfume and Body Mist', 'Fragrances and body mists', 1),
('Sandals', 'Open-toe sandals', 1),
('Shirts', 'Shirts and button-down tops', 1),
('Socks', 'Socks and hosiery', 1),
('Sports Shoes', 'Sports and athletic footwear', 1),
('Sunglasses', 'Sunglasses and eyewear', 1),
('Tops', 'Tops and casual shirts', 1),
('Tshirts', 'T-shirts', 1),
('Wallets', 'Wallets and card holders', 1),
('Watches', 'Watches and wristwear', 1);

-- Existing installations may already contain some of these names and the
-- legacy schema does not enforce uniqueness. Keep the oldest row for each.
DELETE duplicate
FROM categories AS duplicate
JOIN categories AS original
  ON original.name = duplicate.name
 AND original.id < duplicate.id;

DELETE FROM categories
WHERE name NOT IN (
    'Backpacks', 'Belts', 'Briefs', 'Casual Shoes', 'Flip Flops',
    'Formal Shoes', 'Handbags', 'Heels', 'Jeans', 'Kurtas',
    'Perfume and Body Mist', 'Sandals', 'Shirts', 'Socks',
    'Sports Shoes', 'Sunglasses', 'Tops', 'Tshirts', 'Wallets', 'Watches'
);

SET @category_name_index = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'categories'
      AND index_name = 'uq_categories_name'
);
SET @sql = IF(@category_name_index = 0,
    'ALTER TABLE categories ADD UNIQUE KEY uq_categories_name (name)',
    'SELECT 1');
PREPARE category_index_stmt FROM @sql;
EXECUTE category_index_stmt;
DEALLOCATE PREPARE category_index_stmt;
