<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
if($id<=0){
    header('Location: shop.php');
    exit;
}
// Handle add to cart
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    $qty=max(1,(int)($_POST['qty']??1));
    $chk=$pdo->prepare("SELECT id, stock FROM products WHERE id=?");
    $chk->execute([$id]);
    $row=$chk->fetch();
    if($row){
        if($qty > (int)$row['stock']) $qty=(int)$row['stock'];
        $_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+$qty;
        if($_SESSION['cart'][$id] > (int)$row['stock']) $_SESSION['cart'][$id]=(int)$row['stock'];
        $msg='Đã thêm '.$qty.' sản phẩm vào giỏ hàng!';
    }
}

// Fetch product
$stmt=$pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$stmt->execute([$id]);
$product=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){
    http_response_code(404);
    echo '<h1>Sản phẩm không tồn tại</h1><a href="shop.php">Quay lại Chợ</a>';
    exit;
}
$discount=0;
if(!empty($product['old_price']) && $product['old_price']>$product['price']) $discount=round((1-$product['price']/$product['old_price'])*100);
$saving = !empty($product['old_price']) ? max(0,(float)$product['old_price']-(float)$product['price']) : 0;

// related
$related=$pdo->prepare("SELECT p.* FROM products p WHERE p.category_id=? AND p.id!=? ORDER BY p.id DESC LIMIT 4");
$related->execute([$product['category_id'],$id]);
$relatedProducts=$related->fetchAll(PDO::FETCH_ASSOC);

$displayName=getDisplayName();
$cartCount=getCartCount();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($product['name']); ?> | Chợ gia dụng lớn nhất VN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        *{font-family:'Montserrat',sans-serif;}
        .nav-link:hover{color:#ff6b6b;transition:color 0.3s ease;}
        header{transition:all 0.3s ease;}
        header.scrolled{padding:8px 0 !important;}
        header.scrolled .logo{width:100px !important;}
        .hamburger-menu{display:none;}
        .mobile-menu{position:fixed;top:60px;left:0;width:100%;background:#1a1a1a;max-height:0;overflow:hidden;transition:max-height 0.3s ease;z-index:40;}
        .mobile-menu.active{max-height:400px;}
        .mobile-menu a{display:block;padding:12px 16px;color:white;text-decoration:none;border-bottom:1px solid #333;transition:background 0.2s ease;}
        .mobile-menu a:hover{background:#333;color:#ff6b6b;}
        .image-gallery{display:flex;gap:12px;margin-bottom:20px;flex-direction:column;}
        .main-image{width:100%;height:400px;background:#f3f4f6;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;}
        .main-image img{width:100%;height:100%;object-fit:cover;}
        .thumbnail-images{display:flex;gap:8px;overflow-x:auto;padding-bottom:8px;}
        .thumbnail{width:80px;height:80px;border-radius:6px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color 0.3s ease;background:#f3f4f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .thumbnail img{width:100%;height:100%;object-fit:cover;}
        .thumbnail.active{border-color:#ff6b6b;}
        .option-group{margin-bottom:20px;}
        .option-label{font-weight:600;margin-bottom:10px;color:#374151;}
        .options-container{display:flex;gap:8px;flex-wrap:wrap;}
        .option-btn{padding:8px 16px;border:2px solid #e5e7eb;border-radius:6px;background:white;cursor:pointer;transition:all 0.3s ease;font-size:14px;font-weight:500;}
        .option-btn:hover{border-color:#ff6b6b;color:#ff6b6b;}
        .option-btn.active{background:#ff6b6b;color:white;border-color:#ff6b6b;}
        .quantity-selector{display:flex;align-items:center;gap:8px;width:fit-content;}
        .quantity-btn{width:36px;height:36px;border:1px solid #e5e7eb;background:white;cursor:pointer;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;}
        .quantity-btn:hover{border-color:#ff6b6b;color:#ff6b6b;}
        .quantity-input{width:50px;text-align:center;border:1px solid #e5e7eb;padding:6px;border-radius:4px;font-size:14px;}
        .tabs-container{border-bottom:2px solid #e5e7eb;margin-top:40px;}
        .tabs-header{display:flex;gap:20px;margin-bottom:-2px;overflow-x:auto;}
        .tab-btn{padding:16px 0;border-bottom:2px solid transparent;background:none;border:none;cursor:pointer;font-weight:600;color:#6b7280;transition:all 0.3s ease;white-space:nowrap;}
        .tab-btn:hover{color:#1f2937;}
        .tab-btn.active{color:#ff6b6b;border-bottom-color:#ff6b6b;}
        .tab-content{display:none;padding:24px 0;animation:fadeIn 0.3s ease;}
        .tab-content.active{display:block;}
        @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
        .review-item{padding:16px;border-bottom:1px solid #e5e7eb;}
        .review-item:last-child{border-bottom:none;}
        .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:24px;}
        @media (max-width:768px){.products-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));}.main-image{height:300px;}}
        .product-card{transition:transform 0.3s ease,box-shadow 0.3s ease;}
        .product-card:hover{transform:translateY(-8px);box-shadow:0 10px 25px rgba(0,0,0,0.15);}
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-8 flex-1">
                <a href="index.php"><img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 cursor-pointer hover:opacity-80 transition" /></a>
                <div class="nav-menu hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium">Chợ</a>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
                </div>
            </div>
            <div class="search-bar hidden md:flex flex-1 max-w-xs mx-6">
                <div class="w-full relative">
                    <input type="text" placeholder="Tìm kiếm..." id="headerSearchInput" class="w-full px-4 py-2 rounded-lg text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                    <button class="absolute right-2 top-2 text-gray-600" id="headerSearchBtn"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="right-icons flex items-center gap-6">
                <div class="hidden md:flex items-center gap-2 relative group">
                    <div class="flex items-center gap-2 cursor-pointer hover:text-red-500"><i class="fas fa-user text-xl"></i><span class="text-sm font-medium"><?php echo $displayName; ?></span></div>
                    <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow-lg w-48 py-2 z-50">
                        <?php if(isLoggedIn()): ?>
                            <div class="px-4 py-2 text-sm border-b"><div class="font-semibold truncate"><?php echo $displayName; ?></div></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm hover:bg-gray-50 text-red-500">Đăng xuất</a>
                        <?php else: ?>
                            <a href="login.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng nhập</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="flex items-center gap-2 cursor-pointer hover:text-red-500 relative"><i class="fas fa-shopping-cart text-xl"></i><span class="text-sm font-medium hidden sm:inline">Giỏ hàng</span><span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
                <button class="hamburger-menu text-xl focus:outline-none"><i class="fas fa-bars"></i></button>
            </div>
        </nav>
    </header>
    <div class="mobile-menu" id="mobileMenu"><a href="index.php">Trang chủ</a><a href="shop.php">Chợ</a><a href="about.php">Về chúng tôi</a><?php if(isLoggedIn()): ?><a href="logout.php">Đăng xuất</a><?php else: ?><a href="login.php">Đăng nhập</a><?php endif; ?></div>

    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-3 text-sm text-gray-600">
            <a href="index.php" class="hover:text-red-500">Trang chủ</a><span class="mx-2">/</span>
            <a href="shop.php" class="hover:text-red-500">Chợ</a><span class="mx-2">/</span>
            <?php if(!empty($product['cat_name'])): ?><a href="shop.php?cat=<?php echo htmlspecialchars($product['cat_slug']); ?>" class="hover:text-red-500"><?php echo htmlspecialchars($product['cat_name']); ?></a><span class="mx-2">/</span><?php endif; ?>
            <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if($msg): ?><div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm flex items-center justify-between"><span><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($msg); ?></span><a href="cart.php" class="font-bold underline">Xem giỏ hàng</a></div><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-white rounded-lg shadow-sm p-6 mb-8">
            <div>
                <div class="main-image" id="mainImage">
                    <?php if(!empty($product['image'])): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                    <?php else: ?><div class="text-6xl text-gray-300"><i class="fas fa-image"></i></div><?php endif; ?>
                </div>
                <div class="thumbnail-images mt-3">
                    <?php for($t=0;$t<4;$t++): ?>
                    <div class="thumbnail <?php echo $t==0?'active':''; ?>" onclick="changeMainImage(this)">
                        <?php if(!empty($product['image'])): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="thumb" />
                        <?php else: ?><i class="fas fa-image text-gray-400"></i><?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div>
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="flex items-center gap-2"><span class="text-sm text-gray-600">Danh mục:</span><a href="shop.php?cat=<?php echo htmlspecialchars($product['cat_slug']); ?>" class="text-sm font-medium text-red-500 hover:underline"><?php echo htmlspecialchars($product['cat_name']); ?></a></div>
                    </div>
                    <?php if($discount>0): ?><span class="bg-red-500 text-white px-4 py-2 rounded-full text-sm font-bold">-<?php echo $discount; ?>%</span><?php endif; ?>
                </div>
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-200">
                    <div class="flex gap-1"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?php echo $i<=round($product['rating'])?'text-yellow-400':'text-gray-300'; ?>"></i><?php endfor; ?></div>
                    <span class="text-sm text-gray-600"><?php echo number_format((float)$product['rating'],1); ?> / 5.0</span>
                    <span class="text-sm text-gray-600">(<?php echo (int)$product['review_count']; ?> đánh giá)</span>
                </div>
                <div class="mb-6">
                    <div class="flex items-center gap-4 mb-2"><span class="text-3xl font-bold text-red-500"><?php echo formatPrice($product['price']); ?></span><?php if($discount>0): ?><span class="text-lg text-gray-500 line-through"><?php echo formatPrice($product['old_price']); ?></span><?php endif; ?></div>
                    <?php if($saving>0): ?><p class="text-sm text-gray-600">Tiết kiệm: <span class="font-bold text-red-500"><?php echo formatPrice($saving); ?></span></p><?php endif; ?>
                </div>
                <div class="mb-6 p-3 <?php echo (int)$product['stock']>0?'bg-green-50 border-green-200 text-green-800':'bg-red-50 border-red-200 text-red-800'; ?> border rounded-lg">
                    <p class="text-sm"><i class="fas <?php echo (int)$product['stock']>0?'fa-check-circle':'fa-times-circle'; ?> mr-2"></i><?php echo (int)$product['stock']>0?('Còn '.(int)$product['stock'].' sản phẩm trong kho'):'Hết hàng'; ?></p>
                </div>

                <form method="post" class="space-y-6">
                    <div class="option-group">
                        <div class="option-label">Số Lượng</div>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" onclick="decreaseQuantity()"><i class="fas fa-minus"></i></button>
                            <input type="number" id="quantityInput" name="qty" class="quantity-input" value="1" min="1" max="<?php echo (int)$product['stock']; ?>" />
                            <button type="button" class="quantity-btn" onclick="increaseQuantity()"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" name="add_to_cart" <?php echo (int)$product['stock']<=0?'disabled':''; ?> class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3 rounded-lg transition font-bold text-lg flex items-center justify-center gap-2"><i class="fas fa-shopping-cart"></i>Thêm vào giỏ hàng</button>
                        <a href="cart.php" class="flex-1 border-2 border-red-500 text-red-500 hover:bg-red-50 py-3 rounded-lg transition font-bold text-lg flex items-center justify-center gap-2 text-center">Xem giỏ</a>
                    </div>
                </form>

                <div class="mt-6 space-y-3 text-sm text-gray-600">
                    <div class="flex items-center gap-3"><i class="fas fa-truck text-red-500"></i><span>Miễn phí vận chuyển cho đơn hàng từ 500.000đ</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-shield-alt text-red-500"></i><span>Bảo hành chính hãng 12 tháng</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-undo text-red-500"></i><span>Đổi trả trong 30 ngày nếu không hài lòng</span></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab(event,'description')">Mô Tả Sản Phẩm</button>
                    <button class="tab-btn" onclick="switchTab(event,'specifications')">Thông Số Kỹ Thuật</button>
                    <button class="tab-btn" onclick="switchTab(event,'reviews')">Đánh Giá & Nhận Xét</button>
                </div>
            </div>
            <div id="description" class="tab-content active">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Mô Tả Sản Phẩm</h3>
                <div class="text-gray-700 leading-relaxed space-y-4">
                    <p><?php echo nl2br(htmlspecialchars($product['description']??'Chưa có mô tả chi tiết.')); ?></p>
                    <p><strong>Đặc điểm nổi bật:</strong></p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Vật liệu cao cấp, an toàn cho sức khỏe</li>
                        <li>Thiết kế hiện đại, bền đẹp theo thời gian</li>
                        <li>Bảo hành chính hãng 12 tháng</li>
                        <li>Giao hàng toàn quốc, kiểm tra trước khi thanh toán</li>
                    </ul>
                </div>
            </div>
            <div id="specifications" class="tab-content">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Thông Số Kỹ Thuật</h3>
                <table class="w-full">
                    <tbody>
                        <tr class="border-b"><td class="py-3 px-4 font-medium text-gray-700 w-1/3">Mã sản phẩm</td><td class="py-3 px-4 text-gray-600">#<?php echo $product['id']; ?></td></tr>
                        <tr class="border-b"><td class="py-3 px-4 font-medium text-gray-700">Danh mục</td><td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($product['cat_name']); ?></td></tr>
                        <tr class="border-b"><td class="py-3 px-4 font-medium text-gray-700">Tồn kho</td><td class="py-3 px-4 text-gray-600"><?php echo (int)$product['stock']; ?></td></tr>
                        <tr class="border-b"><td class="py-3 px-4 font-medium text-gray-700">Đánh giá</td><td class="py-3 px-4 text-gray-600"><?php echo $product['rating']; ?> / 5 (<?php echo $product['review_count']; ?> đánh giá)</td></tr>
                        <tr><td class="py-3 px-4 font-medium text-gray-700">Bảo hành</td><td class="py-3 px-4 text-gray-600">12 tháng</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="reviews" class="tab-content">
                <div class="mb-6 p-6 bg-gray-50 rounded-lg">
                    <div class="flex items-start gap-8 flex-wrap">
                        <div class="text-center"><div class="text-4xl font-bold text-gray-800 mb-2"><?php echo number_format((float)$product['rating'],1); ?></div>
                            <div class="flex gap-1 justify-center mb-2"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?php echo $i<=round($product['rating'])?'text-yellow-400':'text-gray-300'; ?>"></i><?php endfor; ?></div>
                            <div class="text-sm text-gray-600">Dựa trên <?php echo (int)$product['review_count']; ?> đánh giá</div>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <?php foreach([5,4,3,2,1] as $star): $pct = max(5, min(50, (int)($star*10))); ?>
                            <div class="flex items-center gap-3 mb-1"><span class="text-sm text-gray-600 w-12"><?php echo $star; ?> sao</span><div class="flex-1 bg-gray-200 rounded-full h-2"><div class="bg-yellow-400 h-2 rounded-full" style="width:<?php echo $pct; ?>%"></div></div><span class="text-sm text-gray-600 w-8"><?php echo $star*8; ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 text-sm text-gray-600">
                    <div class="border p-4 rounded-lg bg-white">“Rất hài lòng, giao hàng nhanh, sản phẩm đúng mô tả.” — Nguyễn Văn A • 2 ngày trước</div>
                    <div class="border p-4 rounded-lg bg-white">“Chất lượng tốt, giá hợp lý, sẽ ủng hộ tiếp.” — Trần Thị B • 1 tuần trước</div>
                </div>
            </div>
        </div>

        <?php if($relatedProducts): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Sản Phẩm Tương Tự</h2>
            <div class="products-grid">
                <?php foreach($relatedProducts as $rp): $d=0; if(!empty($rp['old_price']) && $rp['old_price']>$rp['price']) $d=round((1-$rp['price']/$rp['old_price'])*100); ?>
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-md flex flex-col">
                    <a href="product-details.php?id=<?php echo $rp['id']; ?>" class="relative block"><div class="bg-gray-200 h-40 flex items-center justify-center overflow-hidden"><?php if(!empty($rp['image'])): ?><img src="<?php echo htmlspecialchars($rp['image']); ?>" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-image text-2xl text-gray-400"></i><?php endif; ?></div><?php if($d>0): ?><span class="absolute top-3 right-3 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">-<?php echo $d; ?>%</span><?php endif; ?></a>
                    <div class="p-4 flex-1 flex flex-col">
                        <a href="product-details.php?id=<?php echo $rp['id']; ?>" class="font-bold text-gray-800 mb-2 text-sm line-clamp-2 hover:text-red-500"><?php echo htmlspecialchars($rp['name']); ?></a>
                        <div class="flex items-center gap-1 mb-2"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star text-xs <?php echo $i<=round($rp['rating'])?'text-yellow-400':'text-gray-300'; ?>"></i><?php endfor; ?><span class="text-xs text-gray-600">(<?php echo (int)$rp['review_count']; ?>)</span></div>
                        <div class="mb-3"><span class="text-lg font-bold text-red-500"><?php echo formatPrice($rp['price']); ?></span><?php if($d>0): ?><span class="text-xs text-gray-500 line-through ml-1"><?php echo formatPrice($rp['old_price']); ?></span><?php endif; ?></div>
                        <a href="product-details.php?id=<?php echo $rp['id']; ?>" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition text-sm font-medium text-center"><i class="fas fa-eye mr-2"></i>Xem chi tiết</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const header=document.querySelector('header');
        const hamburger=document.querySelector('.hamburger-menu');
        const mobileMenu=document.getElementById('mobileMenu');
        window.addEventListener('scroll',()=>{ if(window.scrollY>100) header.classList.add('scrolled'); else header.classList.remove('scrolled'); });
        if(hamburger) hamburger.addEventListener('click',()=>mobileMenu.classList.toggle('active'));
        function changeMainImage(el){
            const src=el.querySelector('img')?.src;
            if(src) document.querySelector('#mainImage img').src=src;
            document.querySelectorAll('.thumbnail').forEach(t=>t.classList.remove('active'));
            el.classList.add('active');
        }
        function increaseQuantity(){ const i=document.getElementById('quantityInput'); i.value=parseInt(i.value||1)+1; }
        function decreaseQuantity(){ const i=document.getElementById('quantityInput'); if(parseInt(i.value)>1) i.value=parseInt(i.value)-1; }
        function switchTab(e,tab){ document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active')); document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active')); document.getElementById(tab).classList.add('active'); e.target.classList.add('active'); }
        document.getElementById('headerSearchBtn')?.addEventListener('click',()=>{ const v=document.getElementById('headerSearchInput').value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); });
        document.getElementById('headerSearchInput')?.addEventListener('keypress',e=>{ if(e.key==='Enter'){ const v=e.target.value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); }});
    </script>
</body>
</html>
