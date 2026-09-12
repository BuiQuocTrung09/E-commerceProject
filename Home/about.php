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
<title>Câu chuyện — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
.script{font-family:'Instrument Serif',serif}
body{background:var(--paper);color:var(--ink)}
.header{background:rgba(253,248,241,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.topbar{background:var(--ink);color:#f5ece0}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:#a84522}
.card{background:white;border:1px solid var(--line);border-radius:22px}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] text-center">Xưởng mở cửa T2–T7 · 9h–18h · Bát Tràng, Gia Lâm, Hà Nội</div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center font-bold">cg</span><span class="serif text-[20px] font-bold">Chợ Gia Dụng</span></a>
    <nav class="hidden md:flex items-center gap-6 text-sm ml-6"><a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="about.php" class="font-semibold underline underline-offset-8">Câu chuyện</a><a href="term.php" class="hover:text-black/60">Điều khoản</a></nav>
    <div class="ml-auto flex items-center gap-2">
      <span class="hidden md:inline-flex items-center gap-2 pill h-10 px-4 text-sm"><i class="fa-regular fa-user"></i><?php echo $displayName; ?></span>
      <a href="cart.php" class="pill h-10 px-4 flex items-center gap-2 text-sm font-medium"><i class="fa-solid fa-bag-shopping"></i>Giỏ<span class="bg-[var(--terracotta)] text-white text-xs min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
      <a href="shop.php" class="hidden md:inline-flex btn-terra px-5 h-10 items-center text-sm font-semibold">Dạo chợ</a>
    </div>
  </div>
</header>

<section class="max-w-[1280px] mx-auto px-4 pt-6">
  <div class="grid lg:grid-cols-[1.15fr_.85fr] gap-6">
    <div class="bg-[#1c1916] text-[#fdf8f1] rounded-[28px] p-8 md:p-10 relative overflow-hidden">
      <div class="absolute -right-16 -top-16 w-72 h-72 bg-[var(--terracotta)] opacity-15 blur-[50px] rounded-full"></div>
      <div class="relative">
        <div class="inline-flex pill bg-white/5 border-white/15 text-white/80 px-3 py-1 text-xs tracking-[.14em] uppercase">Từ 2014 — Bát Tràng</div>
        <h1 class="serif text-[36px] md:text-[46px] leading-[.95] mt-4">Chúng mình bắt đầu<br>từ một chiếc <span class="script italic font-normal text-[#f0a66a] text-[40px] md:text-[50px]">bát mẻ.</span></h1>
        <p class="text-sm leading-6 text-white/65 mt-4 max-w-[52ch]">Năm 2014, tụi mình đặt một lô bát gốm cho quán cà phê nhỏ. Lô đầu mẻ gần nửa. Người thợ bảo: “Gốm nung củi là vậy — lệch một ly là bỏ.” Từ đó tụi mình học cách chọn đồ: ít hơn, kỹ hơn, và chỉ bán thứ mình dám dùng ở nhà mình.</p>
        <div class="flex gap-3 mt-6">
          <a href="shop.php" class="btn-terra px-6 py-3 text-sm font-semibold">Xem đồ đang bán</a>
          <a href="#lienhe" class="border border-white/20 text-white rounded-full px-6 py-3 text-sm hover:bg-white hover:text-[#1c1916] transition">Liên hệ xưởng</a>
        </div>
      </div>
      <div class="relative mt-8 grid grid-cols-3 gap-3 text-center">
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4"><div class="serif text-2xl font-bold">5.000+</div><div class="text-xs text-white/60">khách giữ lại</div></div>
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4"><div class="serif text-2xl font-bold">12</div><div class="text-xs text-white/60">xưởng nhỏ</div></div>
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4"><div class="serif text-2xl font-bold">30</div><div class="text-xs text-white/60">ngày đổi trả</div></div>
      </div>
    </div>
    <div class="card p-3">
      <div class="bg-[var(--paper2)] border border-dashed border-[var(--line)] rounded-[18px] h-[320px] md:h-[420px] grid place-items-center text-center p-6">
        <div><div class="text-4xl">🏺</div><div class="serif text-lg mt-3">Lò nung củi — 1.280°C</div><div class="text-xs text-[var(--muted)] mt-2 leading-5">Ảnh chụp tại xưởng, Bát Tràng<br>14 tiếng nung · 2 ngày nguội</div><div class="pill inline-flex px-3 py-1 text-xs mt-4 bg-white">Mẻ gốm tháng 8.2024 — đã bán hết</div></div>
      </div>
      <div class="p-4 flex items-center justify-between text-sm"><span class="font-medium">Ghé xưởng xem nung</span><span class="text-xs text-[var(--muted)]">Đặt lịch trước qua email</span></div>
    </div>
  </div>
</section>

<section class="max-w-[1280px] mx-auto px-4 mt-8 grid md:grid-cols-3 gap-4">
  <div class="card p-6"><div class="w-10 h-10 rounded-full bg-[#fdf1e0] border border-[#eadfd1] grid place-items-center text-[#8a5a2b]"><i class="fa-solid fa-hand-sparkles"></i></div><h3 class="font-semibold mt-4">Chọn kỹ, bán ít</h3><p class="text-sm text-[var(--muted)] leading-6 mt-2">Mỗi tháng chỉ vài chục món. Không chạy theo mẫu mới liên tục — chỉ khi thật sự tốt hơn.</p></div>
  <div class="card p-6"><div class="w-10 h-10 rounded-full bg-[#eef3ea] border border-[#d9e2d1] grid place-items-center text-[#6b7a5c]"><i class="fa-solid fa-leaf"></i></div><h3 class="font-semibold mt-4">Tự nhiên & sửa được</h3><p class="text-sm text-[var(--muted)] leading-6 mt-2">Gốm không chì, gang đúc, gỗ sồi — hỏng quai, nứt men đều có thể sửa.</p></div>
  <div class="card p-6"><div class="w-10 h-10 rounded-full bg-[#f5ece0] border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-box-open"></i></div><h3 class="font-semibold mt-4">Gói có tâm</h3><p class="text-sm text-[var(--muted)] leading-6 mt-2">Giấy kraft, rơm lót, dây đay. Không nilon — mở ra thơm mùi gỗ và rơm.</p></div>
</section>

<section class="max-w-[1280px] mx-auto px-4 mt-8">
  <div class="card p-6 md:p-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <h2 class="serif text-[26px]">Vì sao khách ở lại?</h2>
      <span class="pill px-3 py-1.5 text-xs"><span class="w-2 h-2 bg-[#2e7d32] rounded-full inline-block mr-1"></span> 4.9/5 từ 2.400+ đánh giá</span>
    </div>
    <div class="grid md:grid-cols-3 gap-4 mt-6">
      <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-5"><div class="text-[#c9a14a] text-xs">★★★★★</div><div class="text-sm leading-6 mt-3">“Bát cầm ấm tay, màu men lên tự nhiên. Mình mua thêm để làm quà tân gia.”</div><div class="text-xs text-[var(--muted)] mt-3">— Chị Lan, Hà Nội</div></div>
      <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-5"><div class="text-[#c9a14a] text-xs">★★★★★</div><div class="text-sm leading-6 mt-3">“Nồi gang giữ nhiệt rất lâu. Tôi dầu theo hướng dẫn là bóng đẹp.”</div><div class="text-xs text-[var(--muted)] mt-3">— Anh Hùng, TP.HCM</div></div>
      <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-5"><div class="text-[#c9a14a] text-xs">★★★★★</div><div class="text-sm leading-6 mt-3">“Giao hàng nhanh, gói cẩn thận. Thiệp viết tay dễ thương.”</div><div class="text-xs text-[var(--muted)] mt-3">— Quỳnh, Đà Nẵng</div></div>
    </div>
  </div>
</section>

<section id="lienhe" class="max-w-[1280px] mx-auto px-4 mt-8">
  <div class="bg-white border border-[var(--line)] rounded-[28px] p-6 md:p-8 flex flex-col md:flex-row gap-6 items-center justify-between">
    <div><div class="serif text-[22px]">Muốn ghé xưởng hay cần tư vấn?</div><div class="text-sm text-[var(--muted)] mt-2">Nhắn tụi mình — trả lời trong ngày. Hoặc ghé Bát Tràng, tụi mình dẫn đi xem lò.</div><div class="mt-4 flex flex-wrap gap-3 text-sm"><span class="pill px-4 py-2"><i class="fa-regular fa-envelope mr-2"></i>info@chopgiadung.vn</span><span class="pill px-4 py-2"><i class="fa-solid fa-phone mr-2"></i>(028) 3823-8888</span></div></div>
    <div class="flex gap-3 shrink-0"><a href="shop.php" class="btn-terra px-6 py-3 text-sm font-semibold">Dạo chợ</a><a href="term.php" class="pill px-6 py-3 text-sm font-medium bg-white">Điều khoản</a></div>
  </div>
</section>

<footer class="mt-8 border-t border-[var(--line)] bg-[#fdf8f1]">
  <div class="max-w-[1280px] mx-auto px-4 py-10">
    <div class="grid md:grid-cols-[1.4fr_.8fr_.8fr_1fr] gap-8 text-sm">
      <div><div class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-xs font-bold">cg</span><span class="serif font-bold text-lg">Chợ Gia Dụng</span></div><p class="text-[var(--muted)] leading-6 mt-3">Đồ bếp thủ công, bền và có thể sửa. Gói giấy kraft, giao bằng xe nhỏ.</p></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Khám phá</div><ul class="mt-3 space-y-2"><li><a href="shop.php" class="hover:text-[var(--terracotta)]">Chợ</a></li><li><a href="about.php" class="hover:text-[var(--terracotta)]">Câu chuyện</a></li></ul></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Hỗ trợ</div><ul class="mt-3 space-y-2"><li><a href="term.php" class="hover:text-[var(--terracotta)]">Bảo hành & đổi trả</a></li><li class="text-[var(--muted)]">info@chopgiadung.vn</li></ul></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Kết nối</div><div class="flex gap-2 mt-3"><a href="#" class="w-9 h-9 rounded-full bg-white border border-[var(--line)] grid place-items-center"><i class="fa-brands fa-instagram text-sm"></i></a><a href="#" class="w-9 h-9 rounded-full bg-white border border-[var(--line)] grid place-items-center"><i class="fa-brands fa-facebook text-sm"></i></a></div></div>
    </div>
    <div class="text-center text-xs text-[var(--muted)] mt-8 pt-6 border-t border-[var(--line)]">© 2024 Chợ Gia Dụng · Gốm — Gang — Gỗ — Giấy</div>
  </div>
</footer>
</body>
</html>
