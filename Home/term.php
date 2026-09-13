<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$displayName=getDisplayName();
$cartCount=getCartCount();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Điều khoản — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
body{background:var(--paper);color:var(--ink)}
.header{background:rgba(253,248,241,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.card{background:white;border:1px solid var(--line);border-radius:20px}
</style>
</head>
<body class="antialiased">
<header class="header sticky top-0 z-40">
  <div class="max-w-[980px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center font-bold">cg</span><span class="serif text-[18px] font-bold">Chợ Gia Dụng</span></a>
    <nav class="hidden md:flex items-center gap-5 text-sm ml-6"><a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="about.php" class="hover:text-black/60">Câu chuyện</a><a href="term.php" class="font-semibold underline underline-offset-8">Điều khoản</a></nav>
    <div class="ml-auto flex items-center gap-2">
    <div class="relative group">
      <button class="hidden md:inline-flex items-center gap-2 pill h-10 px-4 text-sm">
        <i class="fa-regular fa-user"></i><span class="max-w-[110px] truncate"><?php echo $displayName; ?></span>
        <i class="fa-solid fa-chevron-down text-[10px] opacity-40"></i>
      </button>
      <div class="hidden group-hover:block absolute right-0 top-[44px] bg-white border border-[var(--line)] rounded-2xl shadow-xl w-56 overflow-hidden z-50">
        <?php if(isLoggedIn()): ?>
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-3 text-sm hover:bg-[var(--paper)]"><i class="fa-regular fa-user text-xs opacity-50"></i>Tài khoản của tôi</a>
          <?php if(isAdmin()): ?><a href="admin.php" class="flex items-center gap-2.5 px-4 py-3 text-sm font-medium hover:bg-[var(--paper)]"><i class="fa-solid fa-shield-halved text-xs"></i>Trang quản trị</a><?php endif; ?>
          <a href="logout.php" class="block px-4 py-3 text-sm text-[var(--terracotta)] hover:bg-[var(--paper)] border-t border-[var(--line)]">Đăng xuất</a>
        <?php else: ?>
          <a href="login.php" class="block px-4 py-3 text-sm hover:bg-[var(--paper)]">Đăng nhập</a>
          <a href="register.php" class="block px-4 py-3 text-sm hover:bg-[var(--paper)]">Tạo tài khoản</a>
        <?php endif; ?>
      </div>
    </div>
      <a href="cart.php" class="pill h-10 px-4 flex items-center gap-2 text-sm font-medium"><i class="fa-solid fa-bag-shopping"></i>Giỏ<span class="bg-[var(--terracotta)] text-white text-xs min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
    </div>
  </div>
</header>

<div class="max-w-[980px] mx-auto px-4 pt-6">
  <div class="bg-[#1c1916] text-[#fdf8f1] rounded-[24px] p-7 md:p-8 flex flex-col md:flex-row gap-6 items-start justify-between">
    <div><div class="inline-flex pill bg-white/10 border-white/15 text-white/80 px-3 py-1 text-xs tracking-[.14em] uppercase">Cập nhật 2024</div><h1 class="serif text-[30px] md:text-[36px] leading-none mt-3">Điều khoản dịch vụ</h1><p class="text-sm text-white/60 mt-2 max-w-[50ch]">Viết ngắn gọn, dễ hiểu — để bạn yên tâm mua sắm. Có gì thắc mắc, cứ nhắn tụi mình.</p></div>
    <div class="bg-white text-[#1c1916] rounded-2xl p-4 min-w-[220px]"><div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Liên hệ nhanh</div><div class="text-sm font-medium mt-2"><i class="fa-regular fa-envelope mr-2"></i>info@chopgiadung.vn</div><div class="text-sm font-medium"><i class="fa-solid fa-phone mr-2"></i>(028) 3823-8888</div><div class="text-xs text-[var(--muted)] mt-2">T2–T7 · 9h–18h</div></div>
  </div>

  <div class="grid md:grid-cols-[220px_1fr] gap-6 mt-6 items-start">
    <div class="hidden md:block card p-4 sticky top-[84px]">
      <div class="text-xs tracking-[.14em] uppercase text-[var(--muted)]">Mục lục</div>
      <ul class="mt-3 space-y-1.5 text-sm">
        <li><a href="#s1" class="hover:text-[var(--terracotta)]">1. Phạm vi áp dụng</a></li>
        <li><a href="#s2" class="hover:text-[var(--terracotta)]">2. Tài khoản & bảo mật</a></li>
        <li><a href="#s3" class="hover:text-[var(--terracotta)]">3. Đặt hàng & thanh toán</a></li>
        <li><a href="#s4" class="hover:text-[var(--terracotta)]">4. Vận chuyển & đổi trả</a></li>
        <li><a href="#s5" class="hover:text-[var(--terracotta)]">5. Sở hữu trí tuệ</a></li>
        <li><a href="#s6" class="hover:text-[var(--terracotta)]">6. Tranh chấp</a></li>
      </ul>
      <a href="shop.php" class="mt-4 block text-center pill py-2.5 text-sm font-medium bg-white">Dạo chợ →</a>
    </div>

    <div class="space-y-4">
      <section id="s1" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">1</span> Phạm vi áp dụng</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Điều khoản áp dụng cho mọi truy cập và giao dịch tại Chợ Gia Dụng. Khi dùng website, bạn đồng ý với các điều khoản này.</p></section>
      <section id="s2" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">2</span> Tài khoản & bảo mật</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Giữ mật khẩu riêng tư. Mọi hoạt động từ tài khoản của bạn được xem là do bạn thực hiện.</p><ul class="mt-3 space-y-2 text-sm text-[var(--muted)] list-disc pl-5"><li>Mật khẩu mã hóa (bcrypt)</li><li>Đăng xuất sau khi dùng chung thiết bị</li><li>Có thể đặt lại mật khẩu qua email</li></ul></section>
      <section id="s3" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">3</span> Đặt hàng & thanh toán</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Giá đã gồm VAT. Đơn chỉ xác nhận sau email xác nhận. Hỗ trợ COD, chuyển khoản, thẻ và ví điện tử.</p><div class="mt-4 grid grid-cols-3 gap-3 text-center text-sm"><div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4"><div class="font-semibold">COD</div><div class="text-xs text-[var(--muted)]">Trả khi nhận</div></div><div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4"><div class="font-semibold">Chuyển khoản</div><div class="text-xs text-[var(--muted)]">QR · Xác nhận nhanh</div></div><div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4"><div class="font-semibold">Ví / Thẻ</div><div class="text-xs text-[var(--muted)]">MoMo · Visa</div></div></div></section>
      <section id="s4" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">4</span> Vận chuyển & đổi trả</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Freeship tiêu chuẩn cho đơn từ 500.000đ. Đổi trả 30 ngày nếu lỗi do sản xuất hoặc không đúng mô tả. Hàng thủ công — mỗi chiếc hơi khác nhau là bình thường.</p></section>
      <section id="s5" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">5</span> Sở hữu trí tuệ</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Nội dung, ảnh và thiết kế thuộc Chợ Gia Dụng hoặc được cấp phép. Vui lòng không sao chép khi chưa được phép.</p></section>
      <section id="s6" class="card p-6"><h2 class="font-semibold flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold">6</span> Giải quyết tranh chấp</h2><p class="text-sm leading-6 text-[var(--muted)] mt-3">Ưu tiên thương lượng. Nếu cần, liên hệ <a href="mailto:info@chopgiadung.vn" class="font-semibold text-[var(--ink)] underline">info@chopgiadung.vn</a> hoặc 028-3823-8888 trước khi đưa ra cơ quan có thẩm quyền.</p></section>
      <div class="card p-6 bg-[var(--paper2)] flex flex-col md:flex-row gap-4 items-center justify-between"><div><div class="font-semibold">Còn thắc mắc?</div><div class="text-sm text-[var(--muted)]">Nhắn tụi mình, phản hồi trong ngày.</div></div><div class="flex gap-2 shrink-0"><a href="about.php" class="pill px-5 py-2.5 text-sm font-medium bg-white">Câu chuyện xưởng</a><a href="shop.php" class="px-5 py-2.5 rounded-full bg-[var(--ink)] text-white text-sm font-semibold">Dạo chợ</a></div></div>
    </div>
  </div>
</div>

<footer class="mt-8 border-t border-[var(--line)] bg-[#fdf8f1]">
  <div class="max-w-[980px] mx-auto px-4 py-8 text-center text-xs text-[var(--muted)]">© 2024 Chợ Gia Dụng · Gốm — Gang — Gỗ — Giấy · <a href="index.php" class="underline">Trang chủ</a> · <a href="shop.php" class="underline">Chợ</a></div>
</footer>
</body>
</html>
