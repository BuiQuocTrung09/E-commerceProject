<?php 
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Determine display name from session (server-side). Falls back to 'Tài khoản'.
$displayName = 'Tài khoản';
if (!empty($_SESSION['first_name'])) {
    $displayName = htmlspecialchars($_SESSION['first_name'] . (empty($_SESSION['last_name']) ? '' : ' ' . $_SESSION['last_name']));
} elseif (!empty($_SESSION['email'])) {
    // fallback to email local-part
    $email = $_SESSION['email'];
    $displayName = htmlspecialchars(explode('@', $email)[0]);
}
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

        /* Hover color */
        .nav-link:hover { color: #ff6b6b; transition: color 0.2s ease; }

        /* Product card hover */
        .product-card { transition: transform 0.28s ease, box-shadow 0.28s ease; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(0,0,0,0.12); }

        .banner-slider { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        /* Header scrolled */
        header { transition: all 0.25s ease; }
        header.scrolled { padding: 8px 0 !important; }
        header.scrolled .logo { width: 100px !important; }

        /* Desktop dropdown (also supports keyboard focus) */
        .dropdown-menu { display: none; }
        .dropdown:focus-within > .dropdown-menu,
        .dropdown.group:hover > .dropdown-menu { display: block; }

        /* Mobile menu */
        .mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.28s ease; }
        .mobile-menu.active { max-height: 600px; }

        /* mobile submenu accordion */
        .mobile-submenu { max-height: 0; overflow: hidden; transition: max-height 0.28s ease; }
        .mobile-submenu.active { max-height: 400px; }

        /* z-index so dropdown sits above content */
        .dropdown-menu { z-index: 60; }

        /* small screens adjustments */
        @media (max-width: 767px) {
            header.scrolled .nav-menu { display: none !important; }
            .hamburger-menu { display: inline-flex !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-800">
    <!-- HEADER & NAVBAR -->
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-6 flex-1">
                <!-- LOGO -->
                <img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 sm:w-36 md:w-40 cursor-pointer hover:opacity-80 transition" onclick="scrollToTop()" />

                <!-- MENU (desktop) -->
                <div class="nav-menu hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium">Chợ</a>

                    <!-- Dropdown: use .dropdown group / focus-within for accessibility -->
                    <div class="relative dropdown group">
                        <button class="nav-link text-white text-sm font-medium flex items-center gap-2" aria-haspopup="true" aria-expanded="false">
                            Danh mục <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div class="dropdown-menu hidden group-hover:block absolute left-0 mt-2 bg-white text-gray-800 rounded-lg shadow-lg w-72 p-4" role="menu" aria-label="Danh mục">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <h4 class="font-semibold mb-2">Nấu ăn</h4>
                                    <ul class="space-y-1">
                                        <li><a href="#" class="hover:text-red-500">Nồi & Chảo</a></li>
                                        <li><a href="#" class="hover:text-red-500">Bếp & Phụ kiện</a></li>
                                        <li><a href="#" class="hover:text-red-500">Dao & Dụng cụ</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Bàn ăn</h4>
                                    <ul class="space-y-1">
                                        <li><a href="#" class="hover:text-red-500">Bát Đĩa</a></li>
                                        <li><a href="#" class="hover:text-red-500">Ly & Cốc</a></li>
                                        <li><a href="#" class="hover:text-red-500">Bộ sản phẩm</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Dọn dẹp</h4>
                                    <ul class="space-y-1">
                                        <li><a href="#" class="hover:text-red-500">Cây lau</a></li>
                                        <li><a href="#" class="hover:text-red-500">Hộp rác</a></li>
                                        <li><a href="#" class="hover:text-red-500">Sản phẩm vệ sinh</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-2">Chiếu sáng</h4>
                                    <ul class="space-y-1">
                                        <li><a href="#" class="hover:text-red-500">Đèn trần</a></li>
                                        <li><a href="#" class="hover:text-red-500">Đèn bàn</a></li>
                                        <li><a href="#" class="hover:text-red-500">Đèn LED thông minh</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="#contact" class="nav-link text-white text-sm font-medium">Liên hệ</a>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
                </div>
            </div>

            <!-- SEARCH (hidden on xs) -->
            <div class="search-bar hidden md:flex flex-1 max-w-xs mx-4">
                <div class="w-full relative">
                    <input id="searchInput" type="text" placeholder="Tìm kiếm sản phẩm..." class="w-full px-4 py-2 rounded-lg text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ri[...]" />
                    <button aria-label="Tìm kiếm" class="absolute right-2 top-2 text-gray-600">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- RIGHT ICONS -->
            <div class="right-icons flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 cursor-pointer hover:text-red-500">
                    <i class="fas fa-user text-xl"></i>
                    <span id="accountLabel" class="text-sm font-medium"><?php echo $displayName; ?></span>
                </div>
                <a href="cart.php" class="flex items-center gap-2 cursor-pointer hover:text-red-500 relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="text-sm font-medium hidden sm:inline">Giỏ hàng</span>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                </a>

                <!-- Hamburger (visible on mobile) -->
                <button class="hamburger-menu md:hidden flex items-center text-2xl p-2 focus:outline-none" aria-label="Mở menu" aria-controls="mobileMenu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>

        <!-- MOBILE MENU (single, controlled by hamburger) -->
        <div id="mobileMenu" class="mobile-menu bg-[#1a1a1a] text-white md:hidden px-2">
            <a href="index.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Trang chủ</a>
            <a href="shop.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Chợ</a>

            <!-- Mobile collapsible danh mục -->
            <button id="mobileCategoriesToggle" class="w-full text-left px-4 py-3 border-b border-gray-800 text-white flex items-center justify-between focus:outline-none">
                Danh mục
                <i id="mobileCategoriesIcon" class="fas fa-chevron-down"></i>
            </button>
            <div id="mobileSubmenu" class="mobile-submenu bg-[#111]">
                <a href="#" class="block px-6 py-2 text-gray-200">Nồi & Chảo</a>
                <a href="#" class="block px-6 py-2 text-gray-200">Bếp & Phụ kiện</a>
                <a href="#" class="block px-6 py-2 text-gray-200">Bát Đĩa</a>
                <a href="#" class="block px-6 py-2 text-gray-200">Đèn LED</a>
                <a href="#" class="block px-6 py-2 text-gray-200">Dọn dẹp</a>
            </div>

            <a href="about.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Về chúng tôi</a>
            <a href="term.php" class="block px-4 py-3 border-b border-gray-800 nav-link">Điều khoản dịch vụ</a>
            <a href="#contact" class="block px-4 py-3 border-b border-gray-800 nav-link">Liên hệ</a>
        </div>
    </header>

    <!-- BANNER SLIDER -->
    <section class="banner-slider w-full h-80 md:h-96 flex items-center justify-center relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-30"></div>
        <div class="relative z-10 text-center text-white max-w-4xl px-4">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">Chợ Gia Dụng Lớn Nhất Việt Nam</h1>
            <p class="text-md sm:text-lg md:text-xl mb-6">Mua sắm đồ gia dụng chất lượng cao với giá cả phải chăng</p>
            <button class="bg-red-500 hover:bg-red-600 text-white px-6 sm:px-8 py-2 sm:py-3 rounded-lg font-bold text-md sm:text-lg transition">
                Khám Phá Ngay
            </button>
        </div>
    </section>

    <!-- CATEGORIES SECTION -->
    <section id="categories" class="max-w-7xl mx-auto px-4 py-12 md:py-16">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-8 text-center">Danh Mục Sản Phẩm</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            <!-- Category Card 1 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition">
                <div class="bg-blue-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-utensils text-2xl text-blue-500"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Nồi & Chảo</h3>
                <p class="text-sm text-gray-600">Nồi & Chảo chất lượng</p>
            </div>

            <!-- Category Card 2 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition">
                <div class="bg-green-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-wine-glass text-2xl text-green-500"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Bát Đĩa</h3>
                <p class="text-sm text-gray-600">Bộ bát đĩa đẹp</p>
            </div>

            <!-- Category Card 3 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition">
                <div class="bg-yellow-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-broom text-2xl text-yellow-500"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Dọn Dẹp</h3>
                <p class="text-sm text-gray-600">Dụng cụ làm sạch</p>
            </div>

            <!-- Category Card 4 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:shadow-lg transition">
                <div class="bg-purple-100 w-20 h-20 rounded-lg mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-lightbulb text-2xl text-purple-500"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Chiếu Sáng</h3>
                <p class="text-sm text-gray-600">Đèn & Đồ chiếu sáng</p>
            </div>
        </div>
    </section>

    <!-- FEATURED PRODUCTS -->
    <section id="shop" class="bg-gray-50 py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Sản Phẩm Nổi Bật</h2>
            <p class="text-gray-600 mb-6">Những sản phẩm được yêu thích nhất trong tháng</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Product Card Template (repeat items) -->
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm">
                    <div class="bg-gray-200 h-48 flex items-center justify-center overflow-hidden">
                        <img src="Pics/placeholder.png" alt="Sản phẩm 1" class="w-full h-full object-cover" />
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-gray-800 mb-1">Bộ Nồi 5 Chiếc Cao Cấp</h3>
                        <p class="text-sm text-gray-600 mb-3">Nồi chảo cao cấp, an toàn cho sức khỏe</p>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-yellow-400"><i class="fas fa-star"></i></span>
                            <span class="text-yellow-400"><i class="fas fa-star"></i></span>
                            <span class="text-yellow-400"><i class="fas fa-star"></i></span>
                            <span class="text-yellow-400"><i class="fas fa-star"></i></span>
                            <span class="text-gray-300"><i class="fas fa-star"></i></span>
                            <span class="text-sm text-gray-600">(120)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xl font-bold text-red-500">299.000đ</span>
                                <span class="text-sm text-gray-500 line-through ml-2">450.000đ</span>
                            </div>
                            <button aria-label="Thêm vào giỏ" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg transition flex items-center">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Copy other product cards similarly -->
