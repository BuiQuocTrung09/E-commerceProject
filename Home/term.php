<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Điều Khoản Dịch Vụ | Chợ Gia Dụng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style> *{ font-family: 'Montserrat', sans-serif; } </style>
</head>
<body class="bg-white">
    <!-- Keep header consistent with index.php -->
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-12 flex-1">
                <img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 cursor-pointer hover:opacity-80 transition" onclick="scrollToTop()" />
                <div class="nav-menu hidden md:flex gap-8 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
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
            </div>
        </nav>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-6">Điều Khoản Dịch Vụ</h1>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">1. Phạm vi áp dụng</h2>
            <p class="text-gray-700">Các điều khoản này điều chỉnh việc truy cập và sử dụng website Chợ Gia Dụng. Bằng việc sử dụng trang web, bạn đồng ý với các điều khoản này.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">2. Tài khoản và bảo mật</h2>
            <p class="text-gray-700">Người dùng chịu trách nhiệm giữ bí mật thông tin tài khoản. Mọi hoạt động phát sinh từ tài khoản của bạn được coi là do bạn thực hiện.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">3. Đặt hàng và thanh toán</h2>
            <p class="text-gray-700">Các điều kiện đặt hàng, thanh toán và giao nhận tuân theo quy định được công bố trên website; vui lòng kiểm tra trước khi xác nhận đơn.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">4. Bảo hành và đổi trả</h2>
            <p class="text-gray-700">Thông tin bảo hành, đổi trả được mô tả rõ ràng trong từng sản phẩm. Mọi trường hợp đổi trả phải tuân theo chính sách của chúng tôi.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">5. Quyền sở hữu trí tuệ</h2>
            <p class="text-gray-700">Toàn bộ nội dung trên website thuộc quyền sở hữu của Chợ Gia Dụng hoặc được cấp phép. Không sao chép, tái sử dụng không có sự cho phép.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">6. Giải quyết tranh chấp</h2>
            <p class="text-gray-700">Mọi tranh chấp sẽ được thương lượng hòa giải trước khi đưa ra cơ quan có thẩm quyền.</p>
        </section>

        <p class="text-sm text-gray-500">Cập nhật lần cuối: 2024.</p>
    </main>

    <footer class="bg-[#1a1a1a] text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <a href="about.php" class="text-gray-400 hover:text-red-500 mx-2">Về chúng tôi</a>
            <a href="index.php" class="text-gray-400 hover:text-red-500 mx-2">Trang chủ</a>
            <p class="text-gray-400 text-sm mt-4">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }
    </script>
</body>
</html>