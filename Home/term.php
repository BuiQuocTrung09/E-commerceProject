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
    <title>Điều Khoản Dịch Vụ | Chợ Gia Dụng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>*{font-family:'Montserrat',sans-serif;}.nav-link:hover{color:#ff6b6b}</style>
</head>
<body class="bg-white">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-6 flex-1">
                <a href="index.php"><img src="../Pics/logo_nenden.png" alt="Logo" class="h-10"></a>
                <div class="hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium">Chợ</a>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
                    <a href="term.php" class="nav-link text-red-400 text-sm font-medium">Điều khoản</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 relative group">
                    <div class="flex items-center gap-2 cursor-pointer hover:text-red-400"><i class="fas fa-user"></i><span class="text-sm"><?php echo $displayName; ?></span></div>
                    <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow w-48 py-2 z-50">
                        <?php if(isLoggedIn()): ?><div class="px-4 py-2 text-sm border-b"><div class="font-semibold truncate"><?php echo $displayName; ?></div></div><a href="logout.php" class="block px-4 py-2 text-sm hover:bg-gray-50 text-red-500">Đăng xuất</a><?php else: ?><a href="login.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng nhập</a><a href="register.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng ký</a><?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="flex items-center gap-2 hover:text-red-400 relative"><i class="fas fa-shopping-cart"></i><span class="hidden sm:inline text-sm">Giỏ hàng</span><span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
            </div>
        </nav>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-10">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-8 text-white mb-8">
            <h1 class="text-3xl font-bold mb-2">Điều Khoản Dịch Vụ</h1>
            <p class="opacity-90">Cập nhật lần cuối: 2024 • Hiệu lực cho mọi giao dịch trên chopgiadung.vn</p>
        </div>

        <div class="prose max-w-none">
            <section class="mb-8 bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">1</span> Phạm vi áp dụng</h2>
                <p class="text-gray-700 leading-relaxed">Các điều khoản này điều chỉnh việc truy cập và sử dụng website Chợ Gia Dụng. Bằng việc sử dụng trang web, bạn đồng ý với các điều khoản này. Vui lòng đọc kỹ trước khi đặt hàng.</p>
            </section>
            <section class="mb-8 bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">2</span> Tài khoản và bảo mật</h2>
                <p class="text-gray-700 leading-relaxed">Người dùng chịu trách nhiệm giữ bí mật thông tin tài khoản. Mọi hoạt động phát sinh từ tài khoản của bạn được coi là do bạn thực hiện. Vui lòng không chia sẻ mật khẩu.</p>
                <ul class="list-disc pl-6 mt-3 text-gray-700 text-sm space-y-1">
                    <li>Mật khẩu được mã hóa bằng bcrypt</li>
                    <li>Bạn có thể yêu cầu đặt lại mật khẩu qua email</li>
                    <li>Hãy đăng xuất sau khi dùng chung thiết bị</li>
                </ul>
            </section>
            <section class="mb-8 bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">3</span> Đặt hàng và thanh toán</h2>
                <p class="text-gray-700 leading-relaxed">Giá hiển thị đã bao gồm VAT (nếu có). Đơn hàng chỉ được xác nhận sau khi bạn nhận email xác nhận. Các phương thức thanh toán: COD, chuyển khoản, thẻ, ví điện tử.</p>
                <div class="mt-4 grid md:grid-cols-3 gap-4 text-center text-sm">
                    <div class="bg-gray-50 rounded-lg p-4"><div class="font-bold">COD</div><div class="text-gray-600">Trả khi nhận hàng</div></div>
                    <div class="bg-gray-50 rounded-lg p-4"><div class="font-bold">Chuyển khoản</div><div class="text-gray-600">Chuyển khoản ngân hàng</div></div>
                    <div class="bg-gray-50 rounded-lg p-4"><div class="font-bold">Ví / Thẻ</div><div class="text-gray-600">Thẻ & ví điện tử</div></div>
                </div>
            </section>
            <section class="mb-8 bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">4</span> Vận chuyển & đổi trả</h2>
                <p class="text-gray-700 leading-relaxed">Miễn phí vận chuyển cho đơn từ 500.000đ (vận chuyển tiêu chuẩn). Đổi trả trong 30 ngày nếu sản phẩm lỗi do nhà sản xuất hoặc không đúng mô tả.</p>
            </section>
            <section class="mb-8 bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">5</span> Quyền sở hữu trí tuệ</h2>
                <p class="text-gray-700">Toàn bộ nội dung trên website thuộc quyền sở hữu của Chợ Gia Dụng hoặc được cấp phép. Không sao chép, tái sử dụng không có sự cho phép.</p>
            </section>
            <section class="bg-white border rounded-xl p-6">
                <h2 class="text-xl font-bold mb-3 flex items-center gap-2"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-sm font-bold">6</span> Giải quyết tranh chấp</h2>
                <p class="text-gray-700">Mọi tranh chấp sẽ được thương lượng hòa giải trước khi đưa ra cơ quan có thẩm quyền. Liên hệ: <a href="mailto:info@chopgiadung.vn" class="text-red-500 font-semibold">info@chopgiadung.vn</a> hoặc 028-3823-8888.</p>
            </section>
        </div>
    </main>

    <footer class="bg-[#1a1a1a] text-white py-8 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex gap-4 justify-center text-sm text-gray-400 flex-wrap">
                <a href="about.php" class="hover:text-red-400">Về chúng tôi</a>
                <a href="index.php" class="hover:text-red-400">Trang chủ</a>
                <a href="shop.php" class="hover:text-red-400">Chợ</a>
            </div>
            <p class="text-gray-400 text-sm mt-4">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</body>
</html>
