<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Về Chúng Tôi | Chợ Gia Dụng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { font-family: 'Montserrat', sans-serif; }
        .banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-white">
    <!-- HEADER: you can copy the same header/nav from index.php to keep consistency -->
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-12 flex-1">
                <img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 cursor-pointer hover:opacity-80 transition" onclick="scrollToTop()" />
                <div class="nav-menu hidden md:flex gap-8 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>

                    <!-- Dropdown (kept similar to index.php) -->
                    <div class="relative dropdown group">
                        <button class="nav-link text-white text-sm font-medium flex items-center gap-2">
                            Danh mục <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="dropdown-menu hidden group-hover:block absolute left-0 mt-3 bg-white text-gray-800 rounded-lg shadow-lg w-64 p-4 z-50">
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
                </div>
            </div>

            <div class="right-icons flex items-center gap-6">
                <div class="hidden md:flex items-center gap-2 cursor-pointer hover:text-red-500">
                    <i class="fas fa-user text-xl"></i>
                    <span class="text-sm font-medium">Tài khoản</span>
                </div>
                <div class="flex items-center gap-2 cursor-pointer hover:text-red-500 relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="text-sm font-medium">Giỏ hàng</span>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                </div>
                <button class="hamburger-menu text-xl focus:outline-none md:hidden">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <!-- PAGE BANNER -->
    <section class="banner-slider w-full h-56 flex items-center justify-center relative overflow-hidden banner">
        <div class="relative z-10 text-center text-white max-w-4xl px-4">
            <h1 class="text-4xl font-bold mb-2">Về Chợ Gia Dụng</h1>
            <p class="text-lg mb-0">Cam kết chất lượng - giá tốt - phục vụ tận tâm</p>
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-4">Sứ mệnh của chúng tôi</h2>
        <p class="text-gray-700 mb-6">
            Chợ Gia Dụng ra đời để mang tới các sản phẩm gia dụng chất lượng, an toàn cho gia đình Việt với mức giá hợp lý.
            Chúng tôi tuyển chọn nhà cung cấp cẩn trọng, kiểm tra chất lượng và liên tục cập nhật những mẫu mã phù hợp nhu cầu.
        </p>

        <h3 class="text-xl font-semibold mb-2">Giá trị cốt lõi</h3>
        <ul class="list-disc pl-6 text-gray-700 mb-6">
            <li>Chất lượng làm trọng tâm</li>
            <li>Minh bạch thông tin sản phẩm</li>
            <li>Hậu mãi tốt và hỗ trợ khách hàng nhanh chóng</li>
        </ul>

        <h3 class="text-xl font-semibold mb-2">Đội ngũ</h3>
        <p class="text-gray-700 mb-6">Đội ngũ của chúng tôi gồm những chuyên gia mua sắm, kiểm định chất lượng và chăm sóc khách hàng với nhiều năm kinh nghiệm.</p>

        <div class="bg-gray-50 p-6 rounded-lg">
            <h4 class="font-bold mb-2">Muốn hợp tác hoặc liên hệ?</h4>
            <p class="text-gray-700">Gửi mail tới <a href="mailto:info@chopgiadung.vn" class="text-red-500">info@chopgiadung.vn</a> hoặc gọi (028) 3823 - 8888.</p>
        </div>
    </main>

    <!-- FOOTER: include link to terms -->
    <footer class="bg-[#1a1a1a] text-white py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="mb-4">
                <a href="term.php" class="text-gray-400 hover:text-red-500 mx-2">Điều khoản dịch vụ</a>
                <a href="index.php" class="text-gray-400 hover:text-red-500 mx-2">Trang chủ</a>
            </div>
            <p class="text-gray-400 text-sm">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        // minimal helpers: scrollToTop reused
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        // Note: if you copy header from index.php, include its JS for mobile menu
    </script>
</body>
</html>