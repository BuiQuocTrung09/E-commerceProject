<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Handle add to cart POST
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1,(int)($_POST['qty'] ?? 1));
    if($pid>0){
        // verify product exists
        $chk = $pdo->prepare("SELECT id FROM products WHERE id=?");
        $chk->execute([$pid]);
        if($chk->fetch()){
            $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        }
    }
    header('Location: index.php?added=1');
    exit;
}
$displayName = getDisplayName();
$cartCount = getCartCount();
$added = isset($_GET['added']);

// Fetch featured products (newest 8)
$featured = $pdo->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
// Fetch categories for display
$cats = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trang chủ | Chợ gia dụng lớn nhất VN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { font-family: 'Montserrat', sans-serif; }
        .nav-link:hover { color: #ff6b6b; transition: color 0.2s ease; }
        .product-card { transition: transform 0.28s ease, box-shadow 0.28s ease; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(0,0,0,0.12); }
        .banner-slider { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        header { transition: all 0.25s ease; }
        header.scrolled { padding: 8px 0 !important; }
        header.scrolled .logo { width: 100px !important; }
        .dropdown-menu { display: none; }
        .dropdown:focus-within > .dropdown-menu,
        .dropdown.group:hover > .dropdown-menu { display: block; }
        .mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.28s ease; }
        .mobile-menu.active { max-height: 600px; }
        .mobile-submenu { max-height: 0; overflow: hidden; transition: max-height 0.28s ease; }
        .mobile-submenu.active { max-height: 400px; }
        .dropdown-menu { z-index: 60; }
        @media (max-width: 767px) {
            .hamburger-menu { display: inline-flex !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-800">
    <!-- HEADER & NAVBAR -->
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-6 flex-1">
                <img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 sm:w-36 md:w-40 cursor-pointer hover:opacity-80 transition" onclick="scrollToTop()" />
                <div class="nav-menu hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium">Chợ</a>
                    <div class="relative dropdown group">
                        <button class="nav-link text-white text-sm font-medium flex items-center gap-2" aria-haspopup="true" aria-expanded="false">
                            Danh mục <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="dropdown-menu hidden group-hover:block absolute left-0 mt-2 bg-white text-gray-800 rounded-lg shadow-lg w-72 p-4" role="menu">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <h4 class="font-semibold mb-2">Nấu ăn</h4>
                                    <ul class="space-y-1">
                                        <li><a href="shop.php?cat=noi-chao" class="hover:text-red-500">Nồi & Chảo</a></li>
                                        <li><a href="shop.php?cat=noi-chao" class="hover:text-red-500">Bếp & Phụ kiện</a></li>
                                        <li><a href="shop.php?cat=noi-chao" class="hover:text-red-500">Dao & Dụng cụ</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Bàn ăn</h4>
                                    <ul class="space-y-1">
                                        <li><a href="shop.php?cat=bat-dia" class="hover:text-red-500">Bát Đĩa</a></li>
                                        <li><a href="shop.php?cat=bat-dia" class="hover:text-red-500">Ly & Cốc</a></li>
                                        <li><a href="shop.php?cat=bat-dia" class="hover:text-red-500">Bộ sản phẩm</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Dọn dẹp</h4>
                                    <ul class="space-y-1">
                                        <li><a href="shop.php?cat=don-dep" class="hover:text-red-500">Cây lau</a></li>
                                        <li><a href="shop.php?cat=don-dep" class="hover:text-red-500">Hộp rác</a></li>
                                        <li><a href="shop.php?cat=don-dep" class="hover:text-red-500">Sản phẩm vệ sinh</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Chiếu sáng</h4>
                                    <ul class="space-y-1">
                                        <li><a href="shop.php?cat=chieu-sang" class="hover:text-red-500">Đèn trần</a></li>
                                        <li><a href="shop.php?cat=chieu-sang" class="hover:text-red-500">Đèn bàn</a></li>
                                        <li><a href="shop.php?cat=chieu-sang" class="hover:text-red-500">Đèn LED thông minh</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
                    <a href="#contact" class="nav-link text-white text-sm font-medium">Liên hệ</a>
                </div>
            </div>
            <div class="search-bar hidden md:flex flex-1 max-w-xs mx-4">
                <div class="w-full relative">
                    <input id="searchInput" type="text" placeholder="Tìm kiếm sản phẩm..." class="w-full px-4 py-2 rounded-lg text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                    <button aria-label="Tìm kiếm" id="headerSearchBtn" class="absolute right-2 top-2 text-gray-600"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="right-icons flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 relative group">
                    <div class="flex items-center gap-2 cursor-pointer hover:text-red-500">
                        <i class="fas fa-user text-xl"></i>
                        <span id="accountLabel" class="text-sm font-medium"><?php echo $displayName; ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                    <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow-lg w-48 py-2 z-50">
                        <?php if(isLoggedIn()): ?>
                            <div class="px-4 py-2 text-sm border-b border-gray-100"><div class="font-semibold truncate"><?php echo $displayName; ?></div><div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION['email']??''); ?></div></div>
                            <a href="cart.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Giỏ hàng (<?php echo $cartCount; ?>)</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm hover:bg-gray-50 text-red-500">Đăng xuất</a>
                        <?php else: ?>
                            <a href="login.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng nhập</a>
                            <a href="register.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="flex items-center gap-2 cursor-pointer hover:text-red-500 relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="text-sm font-medium hidden sm:inline">Giỏ hàng</span>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span>
                </a>
                <button class="hamburger-menu md:hidden flex items-center text-2xl p-2 focus:outline-none" aria-label="Mở menu"><i class="fas fa-bars"></i></button>
            </div>
        </nav>
        <div id="mobileMenu" class="mobile-menu bg-[#1a1a1a] text-white md:hidden px-2">
            <a href="index.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Trang chủ</a>
            <a href="shop.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Chợ</a>
            <button id="mobileCategoriesToggle" class="w-full text-left px-4 py-3 border-b border-gray-800 text-white flex items-center justify-between focus:outline-none">
                Danh mục <i id="mobileCategoriesIcon" class="fas fa-chevron-down"></i>
            </button>
            <div id="mobileSubmenu" class="mobile-submenu bg-[#111]">
                <a href="shop.php?cat=noi-chao" class="block px-6 py-2 text-gray-200">Nồi & Chảo</a>
                <a href="shop.php?cat=bat-dia" class="block px-6 py-2 text-gray-200">Bát Đĩa</a>
                <a href="shop.php?cat=don-dep" class="block px-6 py-2 text-gray-200">Dọn dẹp</a>
                <a href="shop.php?cat=chieu-sang" class="block px-6 py-2 text-gray-200">Chiếu sáng</a>
            </div>
            <a href="about.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Về chúng tôi</a>
            <a href="term.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Điều khoản dịch vụ</a>
            <?php if(isLoggedIn()): ?>
                <a href="logout.php" class="block px-4 py-3 nav-link text-red-400">Đăng xuất (<?php echo $displayName; ?>)</a>
            <?php else: ?>
                <a href="login.php" class="block px-4 py-3 nav-link">Đăng nhập</a>
                <a href="register.php" class="block px-4 py-3 nav-link">Đăng ký</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if($added): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-center text-sm">Đã thêm sản phẩm vào giỏ hàng! <a href="cart.php" class="font-bold underline">Xem giỏ hàng</a></div>
    <?php endif; ?>

    <!-- BANNER SLIDER -->
    <section class="banner-slider w-full h-80 md:h-96 flex items-center justify-center relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-30"></div>
        <div class="relative z-10 text-center text-white max-w-4xl px-4">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">Chợ Gia Dụng Lớn Nhất Việt Nam</h1>
            <p class="text-md sm:text-lg md:text-xl mb-6">Mua sắm đồ gia dụng chất lượng cao với giá cả phải chăng</p>
            <a href="shop.php" class="inline-block bg-red-500 hover:bg-red-600 text-white px-6 sm:px-8 py-2 sm:py-3 rounded-lg font-bold text-md sm:text-lg transition">Khám Phá Ngay</a>
        </div>
    </section>

    <!-- CATEGORIES SECTION -->
    <section id="categories" class="max-w-7xl mx-auto px-4 py-12 md:py-16">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-8 text-center">Danh Mục Sản Phẩm</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            <a href="shop.php?cat=noi-chao" class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition block">
                <div class="bg-blue-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center"><i class="fas fa-utensils text-2xl text-blue-500"></i></div>
                <h3 class="font-bold text-gray-800 mb-1">Nồi & Chảo</h3>
                <p class="text-sm text-gray-600">Nồi & Chảo chất lượng</p>
            </a>
            <a href="shop.php?cat=bat-dia" class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition block">
                <div class="bg-green-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center"><i class="fas fa-wine-glass text-2xl text-green-500"></i></div>
                <h3 class="font-bold text-gray-800 mb-1">Bát Đĩa</h3>
                <p class="text-sm text-gray-600">Bộ bát đĩa đẹp</p>
            </a>
            <a href="shop.php?cat=don-dep" class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition block">
                <div class="bg-yellow-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center"><i class="fas fa-broom text-2xl text-yellow-500"></i></div>
                <h3 class="font-bold text-gray-800 mb-1">Dọn Dẹp</h3>
                <p class="text-sm text-gray-600">Dụng cụ làm sạch</p>
            </a>
            <a href="shop.php?cat=chieu-sang" class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition block">
                <div class="bg-purple-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center"><i class="fas fa-lightbulb text-2xl text-purple-500"></i></div>
                <h3 class="font-bold text-gray-800 mb-1">Chiếu Sáng</h3>
                <p class="text-sm text-gray-600">Đèn & Đồ chiếu sáng</p>
            </a>
        </div>
    </section>

    <!-- FEATURED PRODUCTS -->
    <section id="shop" class="bg-gray-50 py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Sản Phẩm Nổi Bật</h2>
                    <p class="text-gray-600">Những sản phẩm được yêu thích nhất trong tháng</p>
                </div>
                <a href="shop.php" class="hidden md:inline-flex items-center gap-2 text-red-500 font-semibold hover:gap-3 transition-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($featured as $p): 
                    $discount = 0;
                    if(!empty($p['old_price']) && $p['old_price']> $p['price']) $discount = round((1 - $p['price']/$p['old_price'])*100);
                ?>
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm flex flex-col">
                    <a href="product-details.php?id=<?php echo $p['id']; ?>" class="bg-gray-200 h-48 flex items-center justify-center overflow-hidden relative block">
                        <?php if($discount>0): ?><span class="absolute top-3 right-3 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">-<?php echo $discount; ?>%</span><?php endif; ?>
                        <?php if(!empty($p['image'])): ?><img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-full h-full object-cover" />
                        <?php else: ?><div class="text-5xl text-gray-400"><i class="fas fa-image"></i></div><?php endif; ?>
                    </a>
                    <div class="p-4 flex-1 flex flex-col">
                        <a href="product-details.php?id=<?php echo $p['id']; ?>" class="font-bold text-gray-800 mb-1 hover:text-red-500 line-clamp-2"><?php echo htmlspecialchars($p['name']); ?></a>
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2 flex-1"><?php echo htmlspecialchars(mb_strimwidth($p['description']??'',0,80,'...')); ?></p>
                        <div class="flex items-center gap-1 mb-3 text-xs">
                            <?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?php echo $i <= round($p['rating']) ? 'text-yellow-400':'text-gray-300'; ?>"></i><?php endfor; ?>
                            <span class="text-gray-500 ml-1">(<?php echo (int)$p['review_count']; ?>)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-lg font-bold text-red-500"><?php echo formatPrice($p['price']); ?></span>
                                <?php if($discount>0): ?><span class="text-xs text-gray-400 line-through ml-1"><?php echo formatPrice($p['old_price']); ?></span><?php endif; ?>
                            </div>
                            <form method="post" class="inline"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="qty" value="1"><button type="submit" name="add_to_cart" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg transition flex items-center text-sm"><i class="fas fa-cart-plus"></i></button></form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-8 md:hidden"><a href="shop.php" class="inline-block border border-red-500 text-red-500 px-6 py-2 rounded-lg font-semibold">Xem tất cả sản phẩm</a></div>
        </div>
    </section>

    <!-- NEWSLETTER / CONTACT -->
    <section id="contact" class="bg-white py-12 border-t">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-2 gap-8 items-center">
            <div>
                <h3 class="text-2xl font-bold mb-2">Đăng ký nhận ưu đãi</h3>
                <p class="text-gray-600">Nhận mã giảm giá 10% cho đơn hàng đầu tiên khi đăng ký email.</p>
            </div>
            <form onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký!');" class="flex gap-2">
                <input type="email" required placeholder="Nhập email của bạn" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <button class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-bold">Đăng ký</button>
            </form>
        </div>
    </section>

    <footer class="bg-[#1a1a1a] text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div><div class="flex items-center gap-2 mb-4"><img src="../Pics/logo_nenden.png" class="h-8"><span class="font-bold">Chợ Gia Dụng</span></div><p class="text-gray-400 text-sm">Chợ gia dụng lớn nhất Việt Nam với hàng ngàn sản phẩm chất lượng cao.</p></div>
                <div><h3 class="font-bold text-lg mb-4">Liên Kết Nhanh</h3><ul class="text-gray-400 text-sm space-y-2"><li><a href="index.php" class="hover:text-red-500">Trang chủ</a></li><li><a href="shop.php" class="hover:text-red-500">Sản phẩm</a></li><li><a href="about.php" class="hover:text-red-500">Về chúng tôi</a></li><li><a href="term.php" class="hover:text-red-500">Điều khoản</a></li></ul></div>
                <div><h3 class="font-bold text-lg mb-4">Hỗ Trợ</h3><ul class="text-gray-400 text-sm space-y-2"><li><span class="text-gray-500">Hotline: (028) 3823-8888</span></li><li><span class="text-gray-500">Email: info@chopgiadung.vn</span></li><li><a href="term.php" class="hover:text-red-500">Chính sách bảo hành</a></li></ul></div>
                <div><h3 class="font-bold text-lg mb-4">Theo dõi</h3><div class="flex gap-3"><a href="#" class="bg-gray-700 hover:bg-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="fab fa-facebook-f"></i></a><a href="#" class="bg-gray-700 hover:bg-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="fab fa-instagram"></i></a><a href="#" class="bg-gray-700 hover:bg-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="fab fa-youtube"></i></a></div></div>
            </div>
            <hr class="border-gray-700 mb-6"><div class="text-center text-gray-400 text-sm">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</div>
        </div>
    </footer>

    <script>
        function scrollToTop(){ window.scrollTo({top:0,behavior:'smooth'}); }
        const header=document.querySelector('header');
        const hamburger=document.querySelector('.hamburger-menu');
        const mobileMenu=document.getElementById('mobileMenu');
        const mobileCategoriesToggle=document.getElementById('mobileCategoriesToggle');
        const mobileSubmenu=document.getElementById('mobileSubmenu');
        const mobileCategoriesIcon=document.getElementById('mobileCategoriesIcon');
        window.addEventListener('scroll',()=>{ if(window.scrollY>60) header.classList.add('scrolled'); else header.classList.remove('scrolled'); });
        if(hamburger) hamburger.addEventListener('click',()=>mobileMenu.classList.toggle('active'));
        if(mobileCategoriesToggle) mobileCategoriesToggle.addEventListener('click',()=>{
            mobileSubmenu.classList.toggle('active');
            mobileCategoriesIcon.classList.toggle('fa-chevron-down');
            mobileCategoriesIcon.classList.toggle('fa-chevron-up');
        });
        // search -> shop
        const sInput=document.getElementById('searchInput');
        const sBtn=document.getElementById('headerSearchBtn');
        function doSearch(){ const v=(sInput.value||'').trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); }
        if(sBtn) sBtn.addEventListener('click',doSearch);
        if(sInput) sInput.addEventListener('keypress',e=>{ if(e.key==='Enter') doSearch(); });
        // hide added toast after 3s
        setTimeout(()=>{ const t=document.querySelector('.bg-green-50'); if(t) t.style.display='none'; },3000);
    </script>
</body>
</html>
