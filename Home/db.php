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

    // Bảng users
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
        slug VARCHAR(150) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        old_price DECIMAL(10,2) DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        description TEXT,
        stock INT DEFAULT 100,
        rating DECIMAL(2,1) DEFAULT 4.5,
        review_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_category 
            FOREIGN KEY (category_id) 
            REFERENCES categories(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
    )");

    // Thêm cột nếu thiếu (cho DB cũ)
    $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN,0);
    $adds = [];
    if(!in_array('slug',$cols)) $adds[] = "ADD COLUMN slug VARCHAR(150) DEFAULT NULL";
    if(!in_array('old_price',$cols)) $adds[] = "ADD COLUMN old_price DECIMAL(10,2) DEFAULT NULL";
    if(!in_array('stock',$cols)) $adds[] = "ADD COLUMN stock INT DEFAULT 100";
    if(!in_array('rating',$cols)) $adds[] = "ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5";
    if(!in_array('review_count',$cols)) $adds[] = "ADD COLUMN review_count INT DEFAULT 0";
    if($adds){
        $pdo->exec("ALTER TABLE products ".implode(', ',$adds));
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, 
        total_price DECIMAL(12,2) NOT NULL, 
        shipping_address VARCHAR(255) NOT NULL, 
        phone VARCHAR(20) NOT NULL, 
        status VARCHAR(50) DEFAULT 'Pending', 
        shipping_method VARCHAR(50) DEFAULT 'standard',
        payment_method VARCHAR(50) DEFAULT 'cod',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    // thêm cột mới cho orders nếu thiếu
    $oCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN,0);
    $oAdds=[];
    if(!in_array('shipping_method',$oCols)) $oAdds[]="ADD COLUMN shipping_method VARCHAR(50) DEFAULT 'standard'";
    if(!in_array('payment_method',$oCols)) $oAdds[]="ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cod'";
    if(!in_array('notes',$oCols)) $oAdds[]="ADD COLUMN notes TEXT";
    if($oAdds) $pdo->exec("ALTER TABLE orders ".implode(', ',$oAdds));

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL, 
        product_id INT NOT NULL, 
        quantity INT NOT NULL, 
        price DECIMAL(10,2) NOT NULL, 
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Seed categories
    $cnt = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if($cnt == 0){
        $pdo->exec("INSERT INTO categories (name, slug) VALUES
            ('Nồi & Chảo','noi-chao'),
            ('Bát Đĩa','bat-dia'),
            ('Dọn Dẹp','don-dep'),
            ('Chiếu Sáng','chieu-sang')");
    }

    // Seed products
    $cnt = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if($cnt == 0){
        $pdo->exec("INSERT INTO products (category_id, name, slug, price, old_price, image, description, stock, rating, review_count) VALUES
            (1,'Bộ Nồi 5 Chiếc Cao Cấp','bo-noi-5-chiec',299000,450000,NULL,'Nồi chảo cao cấp, an toàn cho sức khỏe. Phân phối nhiệt đều, tiết kiệm năng lượng.',245,4.0,120),
            (2,'Bộ Bát Đĩa 24 Chiếc Trắng','bo-bat-dia-24',199000,350000,NULL,'Bộ bát đĩa 24 chiếc men sứ trắng, bền đẹp, an toàn.',180,4.8,85),
            (3,'Bộ Lau Nhà 3 Màu Đa Năng','bo-lau-nha-3-mau',89000,150000,NULL,'Bộ lau nhà 360 độ, vắt khô nhanh, cán inox.',320,4.2,56),
            (4,'Bộ Đèn LED Thông Minh','bo-den-led',450000,650000,NULL,'Đèn LED thông minh điều khiển qua app, đổi 16 triệu màu.',95,5.0,200),
            (1,'Tủ Lạnh Tủ Đông Combo','tu-lanh-combo',1299000,1899000,NULL,'Combo tủ lạnh mini + tủ đông, tiết kiệm điện.',40,4.3,78),
            (4,'Quạt Điều Hòa Không Khí','quat-dieu-hoa',599000,799000,NULL,'Quạt điều hòa hơi nước, làm mát nhanh, ít ồn.',60,4.9,156),
            (1,'Bình Đun Siêu Tốc','binh-dun-sieu-toc',179000,229000,NULL,'Bình đun 1.7L, inox 304, tự ngắt khi sôi.',210,4.1,92),
            (1,'Lò Vi Sóng Điện Tử','lo-vi-song',799000,1199000,NULL,'Lò vi sóng 23L, nướng + vi sóng, hẹn giờ.',35,4.9,134),
            (1,'Nồi Cơm Điện Thông Minh','noi-com-dien',699000,999000,NULL,'Nồi cơm 1.8L, lòng nồi chống dính, hẹn giờ.',88,4.0,210),
            (3,'Bàn Ủi Hơi Nước Tự Động','ban-ui-hoi-nuoc',549000,799000,NULL,'Bàn ủi hơi nước công suất 2400W, chống nhỏ giọt.',55,5.0,89),
            (3,'Máy Rửa Chén Tự Động','may-rua-chen',1949000,2499000,NULL,'Máy rửa chén 12 bộ, tiết kiệm nước, sấy khô.',20,4.8,145),
            (1,'Máy Hút Mùi Mạnh','may-hut-mui',1799000,2499000,NULL,'Máy hút mùi kính cong, lực hút 1200m3/h.',30,4.2,112)
        ");
    }

} catch (PDOException $e) {
    die("Lỗi khởi tạo Database: " . $e->getMessage());
}

// Helper functions
if(!function_exists('formatPrice')){
    function formatPrice($price){
        return number_format((float)$price,0,',','.') . 'đ';
    }
}
if(!function_exists('getCartCount')){
    function getCartCount(){
        if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
        return array_sum($_SESSION['cart']);
    }
}
if(!function_exists('getDisplayName')){
    function getDisplayName(){
        if(!empty($_SESSION['first_name'])) return htmlspecialchars($_SESSION['first_name']. (empty($_SESSION['last_name'])?'':' '.$_SESSION['last_name']));
        if(!empty($_SESSION['email'])) return htmlspecialchars(explode('@',$_SESSION['email'])[0]);
        return 'Tài khoản';
    }
}
if(!function_exists('isLoggedIn')){
    function isLoggedIn(){ return !empty($_SESSION['user_id']); }
}
?>
