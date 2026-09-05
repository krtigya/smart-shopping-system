-- ============================================================
--  Schema updates for multi-image products, variants, and
--  richer brands / categories / products / cart / orders.
--  Database: ecommerce
-- ============================================================

-- ------------------------------------------------------------
-- 1. product_images : multiple images per product, each with
--    its own description and sort order.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    product_id   INT(11)      NOT NULL,
    image        VARCHAR(255) NOT NULL,
    description  VARCHAR(500) DEFAULT NULL,
    sort_order   INT(11)      DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. product_variants : different size / UOM / price / stock
--    per product (optionally linked to a specific image).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_variants (
    id              INT(11)      NOT NULL AUTO_INCREMENT,
    product_id      INT(11)      NOT NULL,
    product_image_id INT(11)     DEFAULT NULL,
    size            VARCHAR(100) DEFAULT NULL,
    uom             VARCHAR(50)  DEFAULT NULL,
    sku             VARCHAR(100) DEFAULT NULL,
    price           DECIMAL(10,2) NOT NULL,
    quantity        INT(11)      NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY product_image_id (product_image_id),
    CONSTRAINT fk_pv_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pv_image FOREIGN KEY (product_image_id)
        REFERENCES product_images(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. Extend brands (description + status)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'brands' AND column_name = 'description');
SET @sql = IF(@col = 0, 'ALTER TABLE brands ADD COLUMN description TEXT DEFAULT NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'brands' AND column_name = 'status');
SET @sql = IF(@col = 0, 'ALTER TABLE brands ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. Extend categories (description + status)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'categories' AND column_name = 'description');
SET @sql = IF(@col = 0, 'ALTER TABLE categories ADD COLUMN description TEXT DEFAULT NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'categories' AND column_name = 'status');
SET @sql = IF(@col = 0, 'ALTER TABLE categories ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5. Extend products (status flag for listing control)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'products' AND column_name = 'status');
SET @sql = IF(@col = 0, 'ALTER TABLE products ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER quantity', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 6. Cart & Orders : track the chosen variant
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'cart' AND column_name = 'variant_id');
SET @sql = IF(@col = 0, 'ALTER TABLE cart ADD COLUMN variant_id INT(11) DEFAULT NULL AFTER product_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = 'ecommerce' AND table_name = 'orders' AND column_name = 'variant_id');
SET @sql = IF(@col = 0, 'ALTER TABLE orders ADD COLUMN variant_id INT(11) DEFAULT NULL AFTER product_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
