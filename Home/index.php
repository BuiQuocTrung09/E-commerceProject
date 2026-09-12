<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1,(int)($_POST['qty'] ?? 1));
    if($pid>0){
        $chk = $pdo->prepare("SELECT id FROM products WHERE id=?");
        $chk->execute([$pid]);
        if($chk->fetch()){ $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty; }
    }
    header('Location: index.php?added=1'); exit;
}
$displayName = getDisplayName();
$cartCount = getCartCount();
$added = isset($_GET['added']);
$featured = $pdo->query("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Chợ Gia Dụng — Bếp ấm, nhà xinh</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--line2:#e7ddd0;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--terracotta2:#a84522;--sage:#6b7a5c;--cream:#fffaf3;--radius:18px}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
.script{font-family:'Instrument Serif',serif}
body{background:var(--paper);color:var(--ink)}
.topbar{background:var(--ink);color:#f5ece0}
.header{background:rgba(253,248,241,.88);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.nav-link{position:relative;font-size:13px;letter-spacing:.02em;color:#3a332d;text-underline-offset:6px}
.nav-link:hover{color:var(--ink)}
.nav-link.active{font-weight:600;color:var(--ink);text-decoration:underline;text-decoration-thickness:1.5px}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:all .2s}
.btn-terra:hover{background:var(--terracotta2);transform:translateY(-1px);box-shadow:0 8px 20px rgba(196,91,47,.25)}
.btn-ghost{border:1px solid var(--line);background:white;border-radius:999px}
.product-card{background:white;border:1px solid var(--line);border-radius:22px;overflow:hidden;transition:all .25s}
.product-card:hover{transform:translateY(-3px);box-shadow:0 18px 40px rgba(28,25,22,.08);border-color:#ddd0beb0}
.img-wrap{background:var(--paper2);position:relative}
.badge-sale{position:absolute;top:12px;left:12px;background:var(--ink);color:#fff;font-size:11px;letter-spacing:.06em;padding:5px 9px;border-radius:999px;font-weight:600}
.price-new{font-family:'Fraunces',serif;font-weight:700;font-size:19px;color:var(--ink)}
.price-old{font-size:12px;color:#9a8e81;text-decoration:line-through}
.star{color:#c9a14a;font-size:11px}
.grain{position:relative}
.grain:before{content:"";position:absolute;inset:0;opacity:.035;pointer-events:none;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.9'/%3E%3C/svg%3E")}
.underline-deco{background:linear-gradient(to right, var(--terracotta) 0, var(--terracotta) 100%);background-size:100% 8px;background-repeat:no-repeat;background-position:0 92%;padding-bottom:2px}
.mobile-menu{max-height:0;overflow:hidden;transition:max-height .28s ease;border-top:1px solid transparent}
.mobile-menu.active{max-height:520px;border-top-color:var(--line)}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase">
  <div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between gap-4">
    <div class="hidden md:flex items-center gap-6"><span><i class="fa-regular fa-envelope mr-2 opacity-60"></i>info@chopgiadung.vn</span><span class="opacity-30">•</span><span><i class="fa-solid fa-phone mr-2 opacity-60"></i>(028) 3823 — 8888</span></div>
    <div class="flex-1 md:flex-none text-center">Miễn phí vận chuyển đơn từ 500.000đ — Đổi trả 30 ngày</div>
    <div class="hidden md:flex items-center gap-3"><a href="about.php" class="hover:text-white/70">Về chúng tôi</a><span class="opacity-30">•</span><a href="term.php" class="hover:text-white/70">Điều khoản</a></div>
  </div>
</div>

<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="flex items-center gap-6 py-4">
      <a href="index.php" class="flex items-center gap-3 shrink-0">
        <span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold tracking-tight">cg</span>
        <span class="leading-none"><span class="serif text-[20px] font-bold tracking-tight block">Chợ Gia Dụng</span><span class="text-[11px] tracking-[.18em] uppercase text-[#7a6e60] block -mt-1">Est. 2014 — Saigon</span></span>
      </a>

      <nav class="hidden lg:flex items-center gap-7 ml-8">
        <a href="index.php" class="nav-link active">Trang chủ</a>
        <a href="shop.php" class="nav-link">Chợ</a>
        <div class="relative group">
          <button class="nav-link flex items-center gap-1.5">Danh mục <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i></button>
          <div class="hidden group-hover:grid absolute left-1/2 -translate-x-1/2 top-[22px] bg-white border border-[var(--line)] rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,.12)] p-6 grid-cols-2 gap-6 w-[520px]">
            <div><div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)] mb-3">Nấu nướng</div><ul class="space-y-2 text-sm"><li><a href="shop.php?cat=noi-chao" class="hover:text-[var(--terracotta)]">Nồi & chảo gang</a></li><li><a href="shop.php?cat=noi-chao" class="hover:text-[var(--terracotta)]">Bếp & phụ kiện</a></li><li><a href="shop.php?cat=noi-chao" class="hover:text-[var(--terracotta)]">Dao & thớt gỗ</a></li></ul></div>
            <div><div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)] mb-3">Sống chậm</div><ul class="space-y-2 text-sm"><li><a href="shop.php?cat=bat-dia" class="hover:text-[var(--terracotta)]">Bát đĩa gốm</a></li><li><a href="shop.php?cat=don-dep" class="hover:text-[var(--terracotta)]">Dọn dẹp & lưu trữ</a></li><li><a href="shop.php?cat=chieu-sang" class="hover:text-[var(--terracotta)]">Ánh sáng ấm</a></li></ul></div>
            <div class="col-span-2 mt-2 bg-[var(--paper2)] rounded-xl p-4 flex items-center justify-between"><span class="text-sm"><span class="font-semibold">Bộ sưu tập Thu 2024</span> — gốm men tro, gỗ sồi tự nhiên</span><a href="shop.php" class="text-sm font-semibold underline">Xem ngay →</a></div>
          </div>
        </div>
        <a href="about.php" class="nav-link">Câu chuyện</a>
      </nav>

      <div class="hidden md:flex items-center flex-1 max-w-[360px] ml-auto">
        <div class="pill flex items-center w-full px-1 h-[42px] gap-1">
          <i class="fa-solid fa-magnifying-glass text-[#9a8e81] ml-3 text-sm"></i>
          <input id="searchInput" placeholder="Tìm nồi gang, bát gốm, đèn giấy..." class="flex-1 bg-transparent outline-none text-[13px] placeholder:text-[#9a8e81] px-2">
          <button id="headerSearchBtn" class="btn-terra px-5 h-[34px] text-[13px] font-semibold shrink-0">Tìm</button>
        </div>
      </div>

      <div class="flex items-center gap-2 ml-auto md:ml-4">
        <div class="hidden md:flex items-center gap-3 pl-2">
          <div class="relative group">
            <button class="pill h-[42px] px-4 flex items-center gap-2.5 text-sm">
              <span class="w-7 h-7 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-regular fa-user"></i></span>
              <span class="max-w-[110px] truncate text-left leading-none"><span class="block text-[11px] tracking-wide uppercase text-[var(--muted)]">Xin chào</span><span class="block font-medium -mt-0.5"><?php echo $displayName; ?></span></span>
              <i class="fa-solid fa-chevron-down text-[10px] opacity-40"></i>
            </button>
            <div class="hidden group-hover:block absolute right-0 top-[46px] bg-white border border-[var(--line)] rounded-2xl shadow-xl w-56 overflow-hidden">
              <?php if(isLoggedIn()): ?>
                <div class="px-4 py-3 bg-[var(--paper)] border-b border-[var(--line)]"><div class="text-sm font-semibold truncate"><?php echo $displayName; ?></div><div class="text-xs text-[var(--muted)] truncate"><?php echo htmlspecialchars($_SESSION['email']??''); ?></div></div>
                <a href="cart.php" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-[var(--paper)]">Giỏ hàng <span class="bg-[var(--ink)] text-white text-xs px-2 py-0.5 rounded-full"><?php echo $cartCount; ?></span></a>
                <a href="logout.php" class="block px-4 py-3 text-sm text-[var(--terracotta)] hover:bg-[var(--paper)]">Đăng xuất</a>
              <?php else: ?>
                <a href="login.php" class="block px-4 py-3 text-sm hover:bg-[var(--paper)]">Đăng nhập</a>
                <a href="register.php" class="block px-4 py-3 text-sm hover:bg-[var(--paper)]">Tạo tài khoản</a>
                <div class="px-4 py-3 text-xs text-[var(--muted)] border-t border-[var(--line)]">Thành viên mới giảm 10% đơn đầu.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <a href="cart.php" class="relative pill h-[42px] px-4 flex items-center gap-2 text-sm font-medium hover:border-[#d8cabd] transition">
          <i class="fa-solid fa-bag-shopping text-[#3a332d]"></i><span class="hidden sm:inline">Giỏ</span>
          <span class="bg-[var(--terracotta)] text-white text-[11px] font-bold min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span>
        </a>
        <button class="lg:hidden pill w-[42px] h-[42px] grid place-items-center" id="hamburger" aria-label="menu"><i class="fa-solid fa-bars"></i></button>
      </div>
    </div>

    <div id="mobileMenu" class="mobile-menu lg:hidden">
      <div class="py-3 space-y-1">
        <div class="flex gap-2 px-1 pb-3">
          <div class="pill flex-1 flex items-center px-3 h-10 gap-2"><i class="fa-solid fa-magnifying-glass text-sm opacity-50"></i><input id="mSearch" placeholder="Tìm sản phẩm..." class="flex-1 bg-transparent outline-none text-sm"></div>
          <button onclick="const v=document.getElementById('mSearch').value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v)" class="btn-terra px-5 h-10 text-sm font-semibold">Tìm</button>
        </div>
        <a href="index.php" class="block px-3 py-3 text-sm font-semibold bg-white border border-[var(--line)] rounded-xl">Trang chủ</a>
        <a href="shop.php" class="block px-3 py-3 text-sm bg-white border border-[var(--line)] rounded-xl">Chợ — tất cả sản phẩm</a>
        <div class="grid grid-cols-2 gap-2">
          <a href="shop.php?cat=noi-chao" class="bg-white border border-[var(--line)] rounded-xl px-3 py-3 text-sm">Nồi & Chảo</a>
          <a href="shop.php?cat=bat-dia" class="bg-white border border-[var(--line)] rounded-xl px-3 py-3 text-sm">Bát đĩa gốm</a>
          <a href="shop.php?cat=don-dep" class="bg-white border border-[var(--line)] rounded-xl px-3 py-3 text-sm">Dọn dẹp</a>
          <a href="shop.php?cat=chieu-sang" class="bg-white border border-[var(--line)] rounded-xl px-3 py-3 text-sm">Ánh sáng</a>
        </div>
        <div class="flex gap-2 pt-2"><?php if(isLoggedIn()): ?><a href="logout.php" class="flex-1 text-center btn-ghost py-3 text-sm">Đăng xuất</a><?php else: ?><a href="login.php" class="flex-1 text-center btn-ghost py-3 text-sm">Đăng nhập</a><a href="register.php" class="flex-1 text-center btn-terra py-3 text-sm">Đăng ký</a><?php endif; ?></div>
      </div>
    </div>
  </div>
</header>

<?php if($added): ?>
<div class="max-w-[1280px] mx-auto px-4 mt-4"><div class="bg-[#f1eadf] border border-[var(--line)] rounded-2xl px-4 py-3 flex items-center justify-between text-sm"><span><span class="inline-flex w-6 h-6 rounded-full bg-[#1c1916] text-white place-items-center justify-center text-xs mr-2">✓</span>Đã thêm vào giỏ — cảm ơn bạn đã chọn đồ thủ công.</span><a href="cart.php" class="font-semibold underline underline-offset-4">Mở giỏ hàng →</a></div></div>
<?php endif; ?>

<!-- HERO -->
<section class="max-w-[1280px] mx-auto px-4 pt-6 md:pt-8">
  <div class="grid lg:grid-cols-[1.05fr_.95fr] gap-6 items-stretch">
    <div class="relative bg-[#1c1916] text-[#fdf8f1] rounded-[28px] overflow-hidden p-7 md:p-10 flex flex-col min-h-[480px] grain">
      <div class="absolute -right-10 -top-10 w-72 h-72 bg-[#c45b2f] opacity-[.12] blur-[60px] rounded-full"></div>
      <div class="absolute -left-12 bottom-10 w-80 h-80 bg-[#e8c9a0] opacity-[.08] blur-[50px] rounded-full"></div>
      <div class="relative">
        <div class="inline-flex items-center gap-2 border border-white/15 rounded-full px-3 py-1.5 text-[11px] tracking-[.14em] uppercase text-white/80"><span class="w-1.5 h-1.5 bg-[#f0a66a] rounded-full animate-pulse"></span> Bộ sưu tập Thu — số lượng có hạn</div>
        <h1 class="serif text-[38px] md:text-[52px] leading-[.92] mt-5">
          Bếp là nơi<br>
          <span class="script font-normal italic text-[#f0a66a] text-[42px] md:text-[56px]">giữ lửa</span> cho<br>
          cả ngôi nhà.
        </h1>
        <p class="text-[14px] leading-6 text-white/70 mt-4 max-w-[36ch]">Đồ gốm nung củi, nồi gang đúc thủ công và đèn giấy — tuyển chọn từ xưởng nhỏ Việt Nam. Bền, ấm, và càng dùng càng đẹp.</p>
        <div class="flex flex-wrap gap-3 mt-7">
          <a href="shop.php" class="btn-terra px-6 py-3 text-sm font-semibold inline-flex items-center gap-2">Khám phá chợ <i class="fa-solid fa-arrow-right text-xs"></i></a>
          <a href="about.php" class="border border-white/20 text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-white hover:text-[#1c1916] transition">Câu chuyện xưởng gốm</a>
        </div>
        <div class="flex items-center gap-4 mt-8 text-xs">
          <div class="flex -space-x-2"><span class="w-8 h-8 rounded-full border-2 border-[#1c1916] bg-[#e8ddd0] grid place-items-center text-[10px] font-bold">4.9</span><span class="w-8 h-8 rounded-full border-2 border-[#1c1916] bg-[#d9cfc0] grid place-items-center"><i class="fa-solid fa-star text-[10px]"></i></span><span class="w-8 h-8 rounded-full border-2 border-[#1c1916] bg-[#fdf8f1] grid place-items-center text-[11px]">★</span></div>
          <div class="leading-tight"><div class="font-semibold text-white">2.400+ đánh giá 5 sao</div><div class="text-white/60">Giao hàng toàn quốc, gói giấy kraft</div></div>
        </div>
      </div>
      <div class="relative mt-auto pt-8 flex gap-3">
        <div class="flex-1 bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur">
          <div class="text-[11px] tracking-[.12em] uppercase text-white/60">Bán chạy tuần này</div>
          <div class="text-sm font-medium mt-1">Bộ nồi gang 3 món — giữ nhiệt 3h</div>
          <div class="text-xs text-white/60 mt-1">Từ <span class="text-white font-semibold">899.000đ</span> <span class="line-through opacity-50">1.250.000đ</span></div>
        </div>
        <div class="hidden md:block w-[140px] bg-[#fdf8f1] rounded-2xl p-2 rotate-[1.2deg]"><div class="h-[86px] bg-[#f5ece0] rounded-xl grid place-items-center text-[#9a8e81]"><i class="fa-solid fa-kitchen-set text-2xl"></i></div><div class="text-xs font-medium text-[#1c1916] mt-2 leading-tight">Gốm Bát Tràng, men tro</div></div>
      </div>
    </div>

    <div class="grid grid-rows-[1.2fr_.8fr] gap-6">
      <div class="bg-white border border-[var(--line)] rounded-[28px] p-3 flex flex-col">
        <div class="img-wrap rounded-[18px] flex-1 min-h-[260px] grid place-items-center overflow-hidden relative">
          <div class="absolute inset-0 opacity-[.06]" style="background:radial-gradient(600px 300px at 70% 20%, #c45b2f 0, transparent 60%), radial-gradient(500px 400px at 10% 90%, #6b7a5c 0, transparent 60%)"></div>
          <div class="relative w-[88%] h-[82%] bg-[#fdf8f1] border border-[var(--line)] rounded-2xl shadow-[0_16px_40px_rgba(0,0,0,.08)] p-4 flex flex-col">
            <div class="flex items-center justify-between text-[11px] tracking-[.12em] uppercase text-[var(--muted)]"><span>Lookbook 04</span><span class="pill px-2 py-1 text-[10px]">Mới về</span></div>
            <div class="flex-1 grid place-items-center"><div class="text-center"><div class="w-20 h-20 mx-auto rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-2xl">🍵</div><div class="serif text-lg font-semibold mt-3">Ấm trà men lam</div><div class="text-xs text-[var(--muted)]">Nung 1.280°C — giữ hương trà</div></div></div>
            <div class="flex items-center justify-between"><span class="price-new text-base">420.000đ</span><span class="text-xs pill px-3 py-1">Còn 18</span></div>
          </div>
          <span class="badge-sale !bg-[var(--terracotta)]">—20% hôm nay</span>
        </div>
        <div class="grid grid-cols-3 gap-3 pt-3">
          <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-3 text-center"><div class="w-10 h-10 mx-auto rounded-full bg-white border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-leaf text-[var(--sage)]"></i></div><div class="text-xs font-medium mt-2">Tự nhiên</div><div class="text-[11px] text-[var(--muted)]">Không chì, không nhựa</div></div>
          <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-3 text-center"><div class="w-10 h-10 mx-auto rounded-full bg-white border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-hand-sparkles text-[var(--terracotta)]"></i></div><div class="text-xs font-medium mt-2">Thủ công</div><div class="text-[11px] text-[var(--muted)]">Làm tay từng chiếc</div></div>
          <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-3 text-center"><div class="w-10 h-10 mx-auto rounded-full bg-white border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-box-open text-[#8a7a5a]"></i></div><div class="text-xs font-medium mt-2">Gói giấy</div><div class="text-[11px] text-[var(--muted)]">Không nilon</div></div>
        </div>
      </div>
      <div class="bg-[var(--paper2)] border border-[var(--line)] rounded-[28px] p-6 flex items-center gap-6">
        <div class="flex-1"><div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)]">Khách kể</div><div class="serif text-[18px] leading-tight mt-2">“Đặt bộ bát đĩa, mở ra thơm mùi rơm. Cầm lên thấy ấm tay — đúng kiểu đồ sẽ dùng cả chục năm.”</div><div class="text-xs text-[var(--muted)] mt-3">— Chị Lan, Hà Nội • mua 3 lần</div></div>
        <div class="hidden md:grid w-[110px] h-[110px] bg-white border border-[var(--line)] rounded-2xl place-items-center text-3xl shrink-0">🏺</div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<section class="max-w-[1280px] mx-auto px-4 mt-6">
  <div class="bg-white border border-[var(--line)] rounded-2xl px-5 py-4 flex flex-wrap gap-4 md:gap-8 items-center justify-between text-sm">
    <span class="inline-flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-truck-fast"></i></span> Giao 1–3 ngày, freeship 500k</span>
    <span class="hidden md:inline h-4 w-px bg-[var(--line)]"></span>
    <span class="inline-flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-rotate-left"></i></span> Đổi trả 30 ngày, không hỏi lý do</span>
    <span class="hidden md:inline h-4 w-px bg-[var(--line)]"></span>
    <span class="inline-flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-certificate"></i></span> Bảo hành 12 tháng, sửa miễn phí</span>
  </div>
</section>

<!-- CATEGORIES -->
<section class="max-w-[1280px] mx-auto px-4 mt-8">
  <div class="flex items-end justify-between gap-4">
    <div><div class="text-[11px] tracking-[.16em] uppercase text-[var(--muted)]">Chọn theo không gian</div><h2 class="serif text-[28px] md:text-[34px] leading-none mt-1">Mỗi góc bếp, một câu chuyện</h2></div>
    <a href="shop.php" class="hidden md:inline-flex items-center gap-2 text-sm font-medium hover:gap-3 transition-all">Tất cả danh mục <i class="fa-solid fa-arrow-right text-xs"></i></a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
    <a href="shop.php?cat=noi-chao" class="group bg-white border border-[var(--line)] rounded-[22px] p-5 hover:border-[#d8cabd] transition">
      <div class="w-12 h-12 rounded-full bg-[#fdf1e0] border border-[#eadfd1] grid place-items-center text-[#8a5a2b]"><i class="fa-solid fa-fire-burner"></i></div>
      <div class="font-semibold mt-4">Nồi & Chảo gang</div><div class="text-sm text-[var(--muted)] leading-tight">Giữ nhiệt sâu, càng tôi càng bóng</div>
      <div class="text-xs font-semibold mt-3 inline-flex items-center gap-1 group-hover:gap-2 transition-all">Xem 48 món <i class="fa-solid fa-arrow-right text-[10px]"></i></div>
    </a>
    <a href="shop.php?cat=bat-dia" class="group bg-white border border-[var(--line)] rounded-[22px] p-5 hover:border-[#d8cabd] transition">
      <div class="w-12 h-12 rounded-full bg-[#eef3ea] border border-[#d9e2d1] grid place-items-center text-[var(--sage)]"><i class="fa-solid fa-bowl-rice"></i></div>
      <div class="font-semibold mt-4">Bát đĩa gốm</div><div class="text-sm text-[var(--muted)] leading-tight">Men tro, men lam — nung củi</div>
      <div class="text-xs font-semibold mt-3 inline-flex items-center gap-1 group-hover:gap-2 transition-all">Xem 32 món <i class="fa-solid fa-arrow-right text-[10px]"></i></div>
    </a>
    <a href="shop.php?cat=don-dep" class="group bg-white border border-[var(--line)] rounded-[22px] p-5 hover:border-[#d8cabd] transition">
      <div class="w-12 h-12 rounded-full bg-[#fdf8f1] border border-[var(--line)] grid place-items-center text-[#9a8e81]"><i class="fa-solid fa-broom"></i></div>
      <div class="font-semibold mt-4">Dọn dẹp & lưu trữ</div><div class="text-sm text-[var(--muted)] leading-tight">Mây tre, gỗ, vải lanh</div>
      <div class="text-xs font-semibold mt-3 inline-flex items-center gap-1 group-hover:gap-2 transition-all">Xem 26 món <i class="fa-solid fa-arrow-right text-[10px]"></i></div>
    </a>
    <a href="shop.php?cat=chieu-sang" class="group bg-white border border-[var(--line)] rounded-[22px] p-5 hover:border-[#d8cabd] transition">
      <div class="w-12 h-12 rounded-full bg-[#fff4e6] border border-[#f0dcc0] grid place-items-center text-[#b07a2a]"><i class="fa-regular fa-lightbulb"></i></div>
      <div class="font-semibold mt-4">Ánh sáng ấm</div><div class="text-sm text-[var(--muted)] leading-tight">Đèn giấy, đèn mây cho buổi tối</div>
      <div class="text-xs font-semibold mt-3 inline-flex items-center gap-1 group-hover:gap-2 transition-all">Xem 19 món <i class="fa-solid fa-arrow-right text-[10px]"></i></div>
    </a>
  </div>
</section>

<!-- FEATURED -->
<section class="max-w-[1280px] mx-auto px-4 mt-10">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <div class="text-[11px] tracking-[.16em] uppercase text-[var(--muted)]">Tuyển chọn của tụi mình</div>
      <h2 class="serif text-[28px] md:text-[34px] leading-none mt-1">Được yêu thích <span class="underline-deco">nhất tháng</span></h2>
      <p class="text-sm text-[var(--muted)] mt-2">Không chạy theo trend — chỉ chọn đồ dùng được lâu, sửa được, và đẹp dần theo năm tháng.</p>
    </div>
    <a href="shop.php" class="btn-ghost px-5 py-2.5 text-sm font-medium hidden md:inline-flex items-center gap-2">Xem chợ đầy đủ <i class="fa-solid fa-arrow-right text-xs"></i></a>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    <?php foreach($featured as $p):
      $discount=0; if(!empty($p['old_price']) && $p['old_price']>$p['price']) $discount=round((1-$p['price']/$p['old_price'])*100);
    ?>
    <div class="product-card flex flex-col">
      <a href="product-details.php?id=<?php echo $p['id']; ?>" class="img-wrap h-[190px] md:h-[210px] grid place-items-center overflow-hidden block">
        <?php if($discount>0): ?><span class="badge-sale">-<?php echo $discount; ?>%</span><?php endif; ?>
        <?php if(!empty($p['image'])): ?><img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-full h-full object-cover">
        <?php else: ?><span class="text-4xl">🍶</span><?php endif; ?>
        <span class="absolute bottom-3 right-3 bg-white/90 backdrop-blur border border-[var(--line)] text-[11px] px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($p['cat_name']??'Thủ công'); ?></span>
      </a>
      <div class="p-4 flex flex-col flex-1">
        <a href="product-details.php?id=<?php echo $p['id']; ?>" class="font-medium leading-tight line-clamp-2 hover:text-[var(--terracotta)] transition"><?php echo htmlspecialchars($p['name']); ?></a>
        <div class="text-xs text-[var(--muted)] mt-1 line-clamp-2"><?php echo htmlspecialchars(mb_strimwidth($p['description']??'',0,72,'…')); ?></div>
        <div class="flex items-center gap-1 mt-3">
          <?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star star <?php echo $i<=round($p['rating'])?'':'opacity-20'; ?>"></i><?php endfor; ?>
          <span class="text-xs text-[var(--muted)] ml-1"><?php echo number_format((float)$p['rating'],1); ?> · <?php echo (int)$p['review_count']; ?> đánh giá</span>
        </div>
        <div class="flex items-end justify-between gap-2 mt-4">
          <div><div class="price-new"><?php echo formatPrice($p['price']); ?></div><?php if($discount>0): ?><div class="price-old"><?php echo formatPrice($p['old_price']); ?></div><?php endif; ?></div>
          <form method="post"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="qty" value="1"><button name="add_to_cart" class="w-10 h-10 rounded-full bg-[var(--ink)] text-white grid place-items-center hover:bg-black transition" aria-label="Thêm vào giỏ"><i class="fa-solid fa-plus text-xs"></i></button></form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-6 md:hidden"><a href="shop.php" class="btn-ghost inline-flex px-6 py-3 text-sm font-medium">Xem tất cả ở chợ</a></div>
</section>

<!-- STORY -->
<section class="max-w-[1280px] mx-auto px-4 mt-10">
  <div class="bg-white border border-[var(--line)] rounded-[28px] overflow-hidden grid md:grid-cols-[1.1fr_.9fr]">
    <div class="p-7 md:p-10">
      <div class="text-[11px] tracking-[.16em] uppercase text-[var(--terracotta)] font-semibold">Từ xưởng gốm Bát Tràng</div>
      <h3 class="serif text-[28px] md:text-[32px] leading-[1.05] mt-2">Chúng mình không bán “đồ gia dụng”.<br>Chúng mình bán <span class="italic font-normal">đồ để dùng cả đời.</span></h3>
      <p class="text-sm text-[var(--muted)] leading-6 mt-4">Mỗi chiếc bát được vuốt tay, phơi ba nắng, rồi nung 14 tiếng. Lệch một ly là bỏ. Vì thế lô hàng mỗi tháng chỉ vài chục chiếc — nhưng cầm lên bạn sẽ hiểu vì sao người ta giữ nó mười năm.</p>
      <div class="flex flex-wrap gap-6 mt-6 text-sm">
        <span class="inline-flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs">♻</span> Sửa & thay nắp trọn đời</span>
        <span class="inline-flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs">✎</span> Khắc tên theo yêu cầu</span>
      </div>
      <a href="about.php" class="inline-flex items-center gap-2 mt-7 text-sm font-semibold underline underline-offset-8 decoration-[var(--line)]">Đọc câu chuyện xưởng →</a>
    </div>
    <div class="bg-[var(--paper2)] border-t md:border-t-0 md:border-l border-[var(--line)] p-6 grid place-items-center">
      <div class="w-full max-w-[360px] bg-white border border-[var(--line)] rounded-[22px] p-4 rotate-[-0.8deg] shadow-[0_16px_40px_rgba(0,0,0,.08)]">
        <div class="h-48 bg-[var(--paper)] border border-dashed border-[var(--line)] rounded-2xl grid place-items-center text-center p-4"><div><div class="text-3xl">🧱</div><div class="text-xs text-[var(--muted)] mt-2">Ảnh lò nung củi — 1.280°C<br>Chụp tại xưởng, tháng 8.2024</div></div></div>
        <div class="flex items-center justify-between mt-4 text-xs"><span class="pill px-3 py-1.5">Gốm mộc · không tráng men</span><span class="font-medium">Từ 320.000đ</span></div>
      </div>
    </div>
  </div>
</section>

<!-- NEWSLETTER -->
<section id="contact" class="max-w-[1280px] mx-auto px-4 mt-8">
  <div class="rounded-[28px] border border-[var(--line)] bg-[#1c1916] text-[#fdf8f1] p-7 md:p-8 flex flex-col md:flex-row gap-6 items-center justify-between">
    <div><div class="serif text-[26px] leading-none">Nhận thư nhà — mỗi tháng một lần</div><div class="text-sm text-white/60 mt-2">Công thức bếp, cách tôi nồi gang, và mã giảm 10% cho lần đầu. Không spam.</div></div>
    <form onsubmit="event.preventDefault(); this.querySelector('button').textContent='Đã gửi ✓'; setTimeout(()=>alert('Cảm ơn bạn!'),200)" class="flex gap-2 w-full md:w-auto">
      <input type="email" required placeholder="Email của bạn" class="flex-1 md:w-[300px] h-[46px] rounded-full px-5 bg-white text-[#1c1916] outline-none text-sm placeholder:text-[#9a8e81]">
      <button class="btn-terra px-7 h-[46px] text-sm font-semibold shrink-0">Gửi</button>
    </form>
  </div>
</section>

<footer class="mt-8 border-t border-[var(--line)] bg-[#fdf8f1]">
  <div class="max-w-[1280px] mx-auto px-4 py-10">
    <div class="grid md:grid-cols-[1.4fr_.8fr_.8fr_1fr] gap-8">
      <div><div class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-xs font-bold">cg</span><span class="serif font-bold text-lg">Chợ Gia Dụng</span></div><p class="text-sm text-[var(--muted)] leading-6 mt-3">Đồ bếp thủ công, bền và có thể sửa. Chúng mình gói bằng giấy kraft, giao bằng xe nhỏ, và luôn trả lời tin nhắn trong ngày.</p><div class="text-xs text-[var(--muted)] mt-4">© 2024 Chợ Gia Dụng · Gốm — Gang — Gỗ — Giấy</div></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Khám phá</div><ul class="mt-3 space-y-2 text-sm"><li><a href="shop.php" class="hover:text-[var(--terracotta)]">Chợ</a></li><li><a href="shop.php?cat=bat-dia" class="hover:text-[var(--terracotta)]">Bát đĩa gốm</a></li><li><a href="about.php" class="hover:text-[var(--terracotta)]">Câu chuyện</a></li></ul></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Hỗ trợ</div><ul class="mt-3 space-y-2 text-sm"><li class="text-[var(--muted)]">Hotline (028) 3823-8888</li><li class="text-[var(--muted)]">info@chopgiadung.vn</li><li><a href="term.php" class="hover:text-[var(--terracotta)]">Bảo hành & đổi trả</a></li></ul></div>
      <div><div class="text-xs tracking-[.14em] uppercase font-semibold text-[var(--muted)]">Ở lại cùng nhau</div><div class="flex gap-2 mt-3"><a href="#" class="w-9 h-9 rounded-full bg-white border border-[var(--line)] grid place-items-center hover:border-[#d8cabd]"><i class="fa-brands fa-instagram text-sm"></i></a><a href="#" class="w-9 h-9 rounded-full bg-white border border-[var(--line)] grid place-items-center hover:border-[#d8cabd]"><i class="fa-brands fa-facebook text-sm"></i></a><a href="#" class="w-9 h-9 rounded-full bg-white border border-[var(--line)] grid place-items-center hover:border-[#d8cabd]"><i class="fa-brands fa-youtube text-sm"></i></a></div><div class="pill inline-flex items-center gap-2 mt-4 px-3 py-2 text-xs"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> Xưởng mở cửa T2–T7, 9h–18h</div></div>
    </div>
  </div>
</footer>

<script>
const header=document.querySelector('.header');
const hamburger=document.getElementById('hamburger');
const mobileMenu=document.getElementById('mobileMenu');
window.addEventListener('scroll',()=>{ if(window.scrollY>10) header.style.boxShadow='0 8px 30px rgba(0,0,0,.06)'; else header.style.boxShadow='none';});
if(hamburger) hamburger.addEventListener('click',()=>mobileMenu.classList.toggle('active'));
function doSearch(){ const v=(document.getElementById('searchInput').value||'').trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); }
document.getElementById('headerSearchBtn')?.addEventListener('click',doSearch);
document.getElementById('searchInput')?.addEventListener('keypress',e=>{ if(e.key==='Enter') doSearch(); });
setTimeout(()=>{ const t=document.querySelector('.bg-green-50,.bg-\\[\\#f1eadf\\]'); if(t&&t.textContent.includes('Đã thêm')) t.style.display='none'; },3200);
</script>
</body>
</html>
