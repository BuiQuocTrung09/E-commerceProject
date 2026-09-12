<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$displayName=getDisplayName();
$cartCount=getCartCount();
?>
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
    <style>*{font-family:'Montserrat',sans-serif;}.banner{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);}.nav-link:hover{color:#ff6b6b}</style>
</head>
<body class="bg-white">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-6 flex-1">
                <a href="index.php"><img src="../Pics/logo_nenden.png" alt="Logo" class="h-10"></a>
                <div class="hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium">Chợ</a>
                    <a href="about.php" class="nav-link text-red-400 text-sm font-medium">Về chúng tôi</a>
                    <a href="term.php" class="nav-link text-white text-sm font-medium">Điều khoản</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 relative group">
                    <div class="flex items-center gap-2 cursor-pointer hover:text-red-400"><i class="fas fa-user"></i><span class="text-sm"><?php echo $displayName; ?></span><i class="fas fa-chevron-down text-xs"></i></div>
                    <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow w-48 py-2 z-50">
                        <?php if(isLoggedIn()): ?><div class="px-4 py-2 text-sm border-b"><div class="font-semibold truncate"><?php echo $displayName; ?></div><div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION['email']); ?></div></div><a href="logout.php" class="block px-4 py-2 text-sm hover:bg-gray-50 text-red-500">Đăng xuất</a><?php else: ?><a href="login.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng nhập</a><a href="register.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng ký</a><?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="flex items-center gap-2 hover:text-red-400 relative"><i class="fas fa-shopping-cart"></i><span class="hidden sm:inline text-sm">Giỏ hàng</span><span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
                <a href="shop.php" class="hidden md:inline-flex bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Mua sắm ngay</a>
            </div>
        </nav>
    </header>

    <section class="banner w-full h-56 flex items-center justify-center relative overflow-hidden">
        <div class="relative z-10 text-center text-white max-w-4xl px-4">
            <h1 class="text-4xl font-bold mb-2">Về Chợ Gia Dụng</h1>
            <p class="text-lg">Cam kết chất lượng - giá tốt - phục vụ tận tâm</p>
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid md:grid-cols-3 gap-8 mb-12">
            <div class="md:col-span-2">
                <h2 class="text-2xl font-bold mb-4">Sứ mệnh của chúng tôi</h2>
                <p class="text-gray-700 mb-6 leading-relaxed">Chợ Gia Dụng ra đời để mang tới các sản phẩm gia dụng chất lượng, an toàn cho gia đình Việt với mức giá hợp lý. Chúng tôi tuyển chọn nhà cung cấp cẩn trọng, kiểm tra chất lượng và liên tục cập nhật mẫu mã phù hợp nhu cầu.</p>
                <h3 class="text-xl font-semibold mb-2">Giá trị cốt lõi</h3>
                <ul class="list-disc pl-6 text-gray-700 mb-6 space-y-1">
                    <li>Chất lượng làm trọng tâm</li>
                    <li>Minh bạch thông tin sản phẩm</li>
                    <li>Hậu mãi tốt và hỗ trợ khách hàng nhanh chóng</li>
                    <li>Giao hàng toàn quốc, đổi trả 30 ngày</li>
                </ul>
                <h3 class="text-xl font-semibold mb-2">Đội ngũ</h3>
                <p class="text-gray-700 mb-6">Đội ngũ chuyên gia mua sắm, kiểm định chất lượng và CSKH nhiều năm kinh nghiệm, luôn sẵn sàng hỗ trợ bạn chọn sản phẩm phù hợp nhất.</p>
                <div class="bg-gray-50 p-6 rounded-lg border">
                    <h4 class="font-bold mb-2">Muốn hợp tác hoặc liên hệ?</h4>
                    <p class="text-gray-700">Gửi mail tới <a href="mailto:info@chopgiadung.vn" class="text-red-500 font-semibold">info@chopgiadung.vn</a> hoặc gọi <span class="font-semibold">(028) 3823 - 8888</span>.</p>
                    <div class="mt-4 flex gap-3"><a href="shop.php" class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-lg text-sm font-semibold">Khám phá sản phẩm</a><a href="term.php" class="border border-gray-300 px-5 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">Điều khoản dịch vụ</a></div>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-white border rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-red-500">5000+</div><div class="text-sm text-gray-600">Khách hàng tin tưởng</div>
                </div>
                <div class="bg-white border rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-red-500">12+</div><div class="text-sm text-gray-600">Danh mục sản phẩm</div>
                </div>
                <div class="bg-white border rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-red-500">24/7</div><div class="text-sm text-gray-600">Hỗ trợ khách hàng</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl p-6 text-white">
                    <h4 class="font-bold mb-2">Ưu đãi thành viên</h4><p class="text-sm opacity-90">Đăng ký ngay để nhận mã giảm 10% cho đơn đầu tiên.</p><a href="register.php" class="mt-3 inline-block bg-white text-purple-600 px-4 py-2 rounded-lg text-sm font-bold">Đăng ký</a>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-8">
            <h3 class="text-xl font-bold mb-6 text-center">Tại sao chọn chúng tôi?</h3>
            <div class="grid md:grid-cols-4 gap-6 text-center">
                <div><div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3 text-blue-500 text-xl"><i class="fas fa-award"></i></div><h4 class="font-semibold">Chính hãng</h4><p class="text-sm text-gray-600">100% hàng chính hãng, có bảo hành</p></div>
                <div><div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 text-green-500 text-xl"><i class="fas fa-truck"></i></div><h4 class="font-semibold">Giao nhanh</h4><p class="text-sm text-gray-600">Giao toàn quốc, miễn phí đơn ≥500k</p></div>
                <div><div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-3 text-yellow-500 text-xl"><i class="fas fa-undo"></i></div><h4 class="font-semibold">Đổi trả 30 ngày</h4><p class="text-sm text-gray-600">Không hài lòng được đổi trả</p></div>
                <div><div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3 text-purple-500 text-xl"><i class="fas fa-headset"></i></div><h4 class="font-semibold">Hỗ trợ 24/7</h4><p class="text-sm text-gray-600">Luôn sẵn sàng giúp bạn</p></div>
            </div>
        </div>
    </main>

    <footer class="bg-[#1a1a1a] text-white py-10 mt-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-wrap gap-4 justify-center text-sm text-gray-400">
                <a href="index.php" class="hover:text-red-400">Trang chủ</a>
                <a href="shop.php" class="hover:text-red-400">Chợ</a>
                <a href="term.php" class="hover:text-red-400">Điều khoản dịch vụ</a>
                <a href="about.php" class="hover:text-red-400 font-semibold text-white">Về chúng tôi</a>
            </div>
            <p class="text-center text-gray-400 text-sm mt-4">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</body>
</html>
