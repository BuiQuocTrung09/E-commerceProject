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
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (phone)
    )");

    // Cột role cho DB cũ
    $uCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN,0);
    if(!in_array('role',$uCols)) $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");

    // Bảng lưu mã OTP đặt lại mật khẩu
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT DEFAULT 0,
        used TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
            ('Chiếu Sáng','chieu-sang')
        ");
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

    // Seed tài khoản admin (đăng nhập: admin@chopgiadung.vn / Admin123)
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $chk->execute(['admin@chopgiadung.vn']);
    if(!$chk->fetch()){
        $ins = $pdo->prepare("INSERT INTO users (first_name,last_name,gender,email,phone,address,city,password,role) VALUES (?,?,?,?,?,?,?,?,?)");
        $ins->execute(['Trung','Quốc','male','admin@chopgiadung.vn','02838238888','Số 1 Lò Rèn','Hà Nội',password_hash('Admin123',PASSWORD_DEFAULT),'admin']);
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
if(!function_exists('isAdmin')){
    function isAdmin(){ return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin'; }
}
if(!function_exists('requireAdmin')){
    function requireAdmin(){
        if(!isLoggedIn()){ header('Location: login.php?redirect=admin.php'); exit; }
        if(!isAdmin()){
            http_response_code(403);
            echo '<div style="font-family:sans-serif;max-width:560px;margin:80px auto;text-align:center">';
            echo '<h1 style="font-size:22px">403 — Chỉ dành cho quản trị viên</h1>';
            echo '<p style="color:#7a6e60">Tài khoản của bạn không có quyền truy cập trang này.</p>';
            echo '<a href="index.php" style="color:#c45b2f">← Về trang chủ</a></div>';
            exit;
        }
    }
}

/* ---------- CSRF ---------- */
if(!function_exists('csrfToken')){
    function csrfToken(){
        if(empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
        return $_SESSION['csrf'];
    }
}
if(!function_exists('csrfField')){
    function csrfField(){ return '<input type="hidden" name="csrf" value="'.csrfToken().'">'; }
}
if(!function_exists('csrfCheck')){
    function csrfCheck(){
        if(($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')){
            http_response_code(419);
            die('Phiên không hợp lệ, vui lòng tải lại trang.');
        }
    }
}

/* ---------- Gửi email (Resend HTTP API, không cần Composer) ---------- */
if(!function_exists('sendEmail')){
    /**
     * Gửi email qua Resend. Cần biến môi trường RESEND_API_KEY.
     * Lưu ý: gói miễn phí của Resend chỉ gửi được tới email chủ tài khoản
     * cho tới khi bạn xác thực riêng domain gửi (xem resend.com/domains).
     * Trả về true nếu gửi thành công, false nếu thất bại / chưa có API key.
     */
    function sendEmail($toEmail, $subject, $htmlBody){
        $apiKey = getenv('RESEND_API_KEY');
        if(!$apiKey){
            // Chưa cấu hình API key — ghi ra file log để thử locally
            @file_put_contents(__DIR__.'/otp_dev.log', date('Y-m-d H:i:s')." | TO: $toEmail | SUBJECT: $subject\n", FILE_APPEND);
            return false;
        }
        $payload = json_encode([
            'from'    => 'Chợ Gia Dụng <onboarding@resend.dev>',
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $htmlBody,
        ]);
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
            ],
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }
}

/* ---------- Email template ấm, giống nhận diện cửa hàng ---------- */
if(!function_exists('emailWrap')){
    function emailWrap($title, $innerHtml){
        return '<div style="background:#fdf8f1;padding:28px 12px;font-family:Arial,Helvetica,sans-serif">'
            .'<div style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #eadfd1;border-radius:18px;overflow:hidden">'
            .'<div style="background:#1c1916;padding:20px 26px;color:#fdf8f1;font-size:15px;font-weight:bold">Chợ Gia Dụng</div>'
            .'<div style="padding:26px;color:#1c1916">'
            .'<h2 style="margin:0 0 12px;font-size:20px">'.$title.'</h2>'
            .$innerHtml
            .'<p style="color:#7a6e60;font-size:12px;margin-top:22px">Nếu bạn không yêu cầu việc này, cứ bỏ qua email — tài khoản của bạn vẫn an toàn.</p>'
            .'</div>'
            .'<div style="background:#fdf8f1;padding:14px 26px;color:#7a6e60;font-size:11px;border-top:1px solid #eadfd1">© '.date('Y').' Chợ Gia Dụng · Gốm — Gang — Gỗ — Giấy</div>'
            .'</div></div>';
    }
}

if(!function_exists('maskEmail')){
    function maskEmail($email){
        $at = strrpos($email,'@');
        if($at === false) return $email;
        $name = substr($email,0,$at);
        $domain = substr($email,$at);
        $keep = substr($name,0,1);
        $dots = substr_count($name,'.') + substr_count($name,'_');
        $dots = max(2,$dots);
        return $keep.str_repeat('•',$dots).$domain;
    }
}
?>
