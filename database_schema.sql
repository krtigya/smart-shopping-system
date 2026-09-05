-- ============================================================
-- Complete Database Schema for Ecommerce Website
-- Database: ecommerce
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS ecommerce DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce;

-- ------------------------------------------------------------
-- 1. users table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email (email),
    KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. categories table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. brands table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS brands (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. products table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT(11) NOT NULL DEFAULT 0,
    category_id INT(11) DEFAULT NULL,
    brand_id INT(11) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_category (category_id),
    KEY idx_brand (brand_id),
    KEY idx_status (status),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. product_images table (multiple images per product)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. product_variants table (size, UOM, price, stock per product)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_variants (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    product_image_id INT(11) DEFAULT NULL,
    size VARCHAR(100) DEFAULT NULL,
    uom VARCHAR(50) DEFAULT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY product_image_id (product_image_id),
    CONSTRAINT fk_pv_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pv_image FOREIGN KEY (product_image_id) REFERENCES product_images(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. cart table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    variant_id INT(11) DEFAULT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_product_variant (user_id, product_id, variant_id),
    KEY idx_user (user_id),
    KEY idx_product (product_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. orders table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    variant_id INT(11) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    order_status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_product (product_id),
    KEY idx_payment_status (payment_status),
    KEY idx_order_status (order_status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. addresses table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS addresses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    location TEXT NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. Sample Data
-- ------------------------------------------------------------

-- Categories
INSERT IGNORE INTO categories (id, name, description, status) VALUES
(1, 'Backpacks', 'Backpacks and daypacks', 1),
(2, 'Belts', 'Belts and waist accessories', 1),
(3, 'Briefs', 'Briefs and underwear', 1),
(4, 'Casual Shoes', 'Casual footwear', 1),
(5, 'Flip Flops', 'Flip flops and sandals', 1),
(6, 'Formal Shoes', 'Formal footwear', 1),
(7, 'Handbags', 'Handbags and purses', 1),
(8, 'Heels', 'Heeled footwear', 1),
(9, 'Jeans', 'Denim jeans', 1),
(10, 'Kurtas', 'Kurtas and traditional tops', 1),
(11, 'Perfume and Body Mist', 'Fragrances and body mists', 1),
(12, 'Sandals', 'Open-toe sandals', 1),
(13, 'Shirts', 'Shirts and button-down tops', 1),
(14, 'Socks', 'Socks and hosiery', 1),
(15, 'Sports Shoes', 'Sports and athletic footwear', 1),
(16, 'Sunglasses', 'Sunglasses and eyewear', 1),
(17, 'Tops', 'Tops and casual shirts', 1),
(18, 'Tshirts', 'T-shirts', 1),
(19, 'Wallets', 'Wallets and card holders', 1),
(20, 'Watches', 'Watches and wristwear', 1);

-- Brands
INSERT IGNORE INTO brands (id, name, description, status) VALUES
(1, 'Ray-Ban', 'Iconic eyewear brand', 1),
(2, 'Oakley', 'Performance eyewear', 1),
(3, 'Gucci', 'Luxury fashion', 1),
(4, 'Prada', 'Italian luxury', 1),
(5, 'Tom Ford', 'Designer accessories', 1);

-- Products
INSERT IGNORE INTO products (id, name, description, price, quantity, category_id, brand_id, image, status) VALUES
(1, 'Classic Aviator Sunglasses', 'Timeless aviator design with UV protection', 12999.00, 50, 1, 1, 'assets/images/sunglass.jpeg', 1),
(2, 'Wayfarer Classic', 'Iconic wayfarer frame in black', 11999.00, 30, 1, 1, 'assets/images/sunglass.jpeg', 1),
(3, 'Leather Bifold Wallet', 'Premium genuine leather bifold wallet', 2999.00, 100, 2, 3, 'assets/images/wallet.jpeg', 1),
(4, 'Silk Scarf', '100% mulberry silk scarf', 4999.00, 75, 3, 4, 'assets/images/scarf.jpeg', 1),
(5, 'Baseball Cap', 'Adjustable cotton baseball cap', 1999.00, 200, 4, 2, 'assets/images/hat.jpg', 1),
(6, 'Leather Belt', 'Full grain leather belt with brass buckle', 3499.00, 60, 5, 5, 'assets/images/wallet.jpeg', 1);

-- Sample Product Images
INSERT IGNORE INTO product_images (id, product_id, image, description, sort_order) VALUES
(1, 1, 'assets/images/sunglass.jpeg', 'Main product image', 0),
(2, 1, 'assets/images/sunglass.jpeg', 'Side view', 1),
(3, 2, 'assets/images/sunglass.jpeg', 'Main product image', 0),
(4, 3, 'assets/images/wallet.jpeg', 'Main product image', 0),
(5, 4, 'assets/images/scarf.jpeg', 'Main product image', 0),
(6, 5, 'assets/images/hat.jpg', 'Main product image', 0),
(7, 6, 'assets/images/wallet.jpeg', 'Main product image', 0);

-- Sample Product Variants
INSERT IGNORE INTO product_variants (id, product_id, product_image_id, size, uom, sku, price, quantity) VALUES
(1, 1, 1, '58mm', 'Pair', 'RB-AVI-58-GLD', 12999.00, 25),
(2, 1, 2, '62mm', 'Pair', 'RB-AVI-62-GLD', 13499.00, 25),
(3, 2, 3, 'Standard', 'Pair', 'RB-WAY-STD-BLK', 11999.00, 30),
(4, 3, 4, 'One Size', 'Piece', 'GCC-WLT-BIF-BRN', 2999.00, 100),
(5, 4, 5, '70x70cm', 'Piece', 'PRD-SCF-SLK-RED', 4999.00, 75),
(6, 5, 6, 'Adjustable', 'Piece', 'OKL-CAP-BSE-NVY', 1999.00, 200),
(7, 6, 7, '32-42 inch', 'Piece', 'TFD-BLT-LTH-BLK', 3499.00, 60);

-- Admin user (password: Admin@123)
INSERT IGNORE INTO users (id, username, email, password, first_name, last_name, role) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');

-- ============================================================
-- Please run this SQL in your database to create the schema and sample data
-- ============================================================
