<?php
$host = 'localhost';
$username = 'root';
$password = ''; 
$dbname = 'ShopGiaDung'; 

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Bảng users được mở rộng để lưu đầy đủ thông tin từ form register.php
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        gender ENUM('male','female','other') DEFAULT 'other',
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20),
        address VARCHAR(255),
        city VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (phone)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_category 
            FOREIGN KEY (category_id) 
            REFERENCES categories(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, 
        total_price DECIMAL(12,2) NOT NULL, 
        shipping_address VARCHAR(255) NOT NULL, 
        phone VARCHAR(20) NOT NULL, 
        status VARCHAR(50) DEFAULT 'Pending', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL, 
        product_id INT NOT NULL, 
        quantity INT NOT NULL, 
        price DECIMAL(10,2) NOT NULL, 
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

} catch (PDOException $e) {
    die("Lỗi khởi tạo Database: " . $e->getMessage());
}
?>
