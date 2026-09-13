<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$id = (int)($_GET['id'] ?? 0);
if($id<=0){ header('Location: shop.php'); exit; }
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
        $msg='Đã thêm '.$qty.' sản phẩm vào giỏ!';
    }
}
$stmt=$pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$stmt->execute([$id]);
$product=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){ http_response_code(404); echo '<div style="padding:40px;font-family:sans-serif">Sản phẩm không tồn tại — <a href="shop.php">quay lại chợ</a></div>'; exit; }
$discount=0; if(!empty($product['old_price']) && $product['old_price']>$product['price']) $discount=round((1-$product['price']/$product['old_price'])*100);
$saving = !empty($product['old_price']) ? max(0,(float)$product['old_price']-(float)$product['price']) : 0;
$related=$pdo->prepare("SELECT p.* FROM products p WHERE p.category_id=? AND p.id!=? ORDER BY p.id DESC LIMIT 4");
$related->execute([$product['category_id'],$id]);
$relatedProducts=$related->fetchAll(PDO::FETCH_ASSOC);
$displayName=getDisplayName();
$cartCount=getCartCount();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo htmlspecialchars($product['name']); ?> — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
body{background:var(--paper);color:var(--ink)}
.header{background:rgba(253,248,241,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.topbar{background:var(--ink);color:#f5ece0}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:#a84522}
.btn-ghost{border:1px solid var(--line);background:white;border-radius:999px}
.card{background:white;border:1px solid var(--line);border-radius:22px}
.img-main{background:var(--paper2);border:1px solid var(--line);border-radius:20px;overflow:hidden}
.thumb{width:72px;height:72px;border-radius:14px;overflow:hidden;border:2px solid transparent;background:var(--paper2);cursor:pointer}
.thumb.active{border-color:var(--ink)}
.price-new{font-family:'Fraunces',serif;font-weight:700;font-size:28px}
.tab-btn{padding:14px 0 12px;border-bottom:2px solid transparent;font-weight:600;color:#7a6e60;white-space:nowrap}
.tab-btn.active{color:var(--ink);border-bottom-color:var(--ink)}
.product-card{background:white;border:1px solid var(--line);border-radius:20px;overflow:hidden}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between"><span class="hidden md:inline">Thủ công · Bền · Sửa được</span><span>Miễn phí vận chuyển đơn từ 500.000đ</span><span class="hidden md:inline">Gói giấy kraft, không nilon</span></div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold">cg</span><span class="serif text-[20px] font-bold">Chợ Gia Dụng</span></a>
    <nav class="hidden md:flex items-center gap-6 text-sm ml-6">
      <a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="about.php" class="hover:text-black/60">Câu chuyện</a>
    </nav>
    <div class="hidden md:flex items-center flex-1 max-w-[320px] ml-auto">
      <div class="pill flex items-center w-full px-1 h-[40px] gap-1"><i class="fa-solid fa-magnifying-glass text-[#9a8e81] ml-3 text-sm"></i><input id="headerSearchInput" placeholder="Tìm..." class="flex-1 bg-transparent outline-none text-[13px] px-2"><button id="headerSearchBtn" class="btn-terra px-4 h-[32px] text-xs font-semibold">Tìm</button></div>
    </div>
    <div class="flex items-center gap-2 ml-auto md:ml-4">
      <div class="relative group">
        <button class="hidden md:flex items-center gap-2.5 pill h-[40px] px-4 text-sm">
          <span class="w-7 h-7 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-regular fa-user"></i></span>
          <span class="max-w-[110px] truncate"><?php echo $displayName; ?></span>
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
      <a href="cart.php" class="relative pill h-[40px] px-4 flex items-center gap-2 text-sm font-medium"><i class="fa-solid fa-bag-shopping"></i><span class="hidden sm:inline">Giỏ</span><span class="bg-[var(--terracotta)] text-white text-[11px] font-bold min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
      <a href="shop.php" class="hidden md:inline-flex lg:hidden pill w-10 h-10 grid place-items-center"><i class="fa-solid fa-bars"></i></a>
    </div>
  </div>
</header>

<div class="max-w-[1280px] mx-auto px-4 py-4 text-[13px] text-[var(--muted)] flex items-center gap-2 flex-wrap">
  <a href="index.php" class="hover:text-[var(--ink)]">Trang chủ</a><span class="opacity-40">/</span><a href="shop.php" class="hover:text-[var(--ink)]">Chợ</a><span class="opacity-40">/</span><?php if($product['cat_name']): ?><a href="shop.php?cat=<?php echo htmlspecialchars($product['cat_slug']); ?>" class="hover:text-[var(--ink)]"><?php echo htmlspecialchars($product['cat_name']); ?></a><span class="opacity-40">/</span><?php endif; ?><span class="text-[var(--ink)] font-medium truncate"><?php echo htmlspecialchars($product['name']); ?></span>
</div>

<div class="max-w-[1280px] mx-auto px-4 pb-10">
  <?php if($msg): ?><div class="bg-[#f1eadf] border border-[var(--line)] rounded-2xl px-4 py-3 flex items-center justify-between text-sm mb-4"><span><span class="w-6 h-6 rounded-full bg-[var(--ink)] text-white grid place-items-center text-xs inline-flex justify-center mr-2">✓</span><?php echo htmlspecialchars($msg); ?></span><a href="cart.php" class="font-semibold underline">Mở giỏ →</a></div><?php endif; ?>

  <div class="grid lg:grid-cols-[1.05fr_.95fr] gap-6">
    <div>
      <div class="img-main h-[420px] md:h-[520px] grid place-items-center relative" id="mainImage">
        <?php if(!empty($product['image'])): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
        <?php else: ?><span class="text-6xl">🍶</span><?php endif; ?>
        <?php if($discount>0): ?><span class="absolute top-4 left-4 bg-[var(--ink)] text-white text-xs font-bold px-3 py-1.5 rounded-full">-<?php echo $discount; ?>% — Tiết kiệm <?php echo formatPrice($saving); ?></span><?php endif; ?>
        <span class="absolute bottom-4 left-4 pill px-3 py-1.5 text-xs bg-white/90 backdrop-blur"><i class="fa-solid fa-certificate text-[var(--terracotta)] mr-1"></i> Nung 1.280°C · Không chì</span>
      </div>
      <div class="flex gap-2 mt-3 overflow-x-auto pb-1">
        <?php for($t=0;$t<4;$t++): ?><div class="thumb shrink-0 grid place-items-center <?php echo $t==0?'active':''; ?>" onclick="changeMainImage(this)"><?php if(!empty($product['image'])): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" class="w-full h-full object-cover"><?php else: ?><span class="text-xl">🍶</span><?php endif; ?></div><?php endfor; ?>
        <div class="hidden md:flex items-center gap-2 ml-2 text-xs text-[var(--muted)]"><span class="w-8 h-px bg-[var(--line)]"></span> Ảnh thật tại xưởng</div>
      </div>
    </div>

    <div class="card p-6 md:p-7">
      <div class="flex items-start justify-between gap-4">
        <div><div class="inline-flex items-center gap-2 pill px-3 py-1 text-xs"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> Còn <?php echo (int)$product['stock']; ?> · Sẵn sàng giao</div><h1 class="serif text-[26px] md:text-[32px] leading-[1.05] mt-3"><?php echo htmlspecialchars($product['name']); ?></h1><a href="shop.php?cat=<?php echo htmlspecialchars($product['cat_slug']); ?>" class="text-sm text-[var(--muted)] hover:text-[var(--ink)]"><?php echo htmlspecialchars($product['cat_name']); ?> · Thủ công</a></div>
        <?php if($discount>0): ?><span class="hidden md:inline-flex bg-[var(--terracotta)] text-white text-xs font-bold px-3 py-1.5 rounded-full h-fit">-<?php echo $discount; ?>%</span><?php endif; ?>
      </div>

      <div class="flex items-center gap-3 mt-4 pb-5 border-b border-[var(--line)]">
        <div class="flex gap-0.5"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star text-xs <?php echo $i<=round($product['rating'])?'text-[#c9a14a]':'text-[#e7ddd0]'; ?>"></i><?php endfor; ?></div>
        <span class="text-sm font-medium"><?php echo number_format((float)$product['rating'],1); ?> / 5</span><span class="text-sm text-[var(--muted)]">· <?php echo (int)$product['review_count']; ?> người đã mua</span>
        <span class="ml-auto hidden md:inline-flex items-center gap-1.5 text-xs pill px-3 py-1"><i class="fa-regular fa-heart"></i> Lưu</span>
      </div>

      <div class="py-5 border-b border-[var(--line)]">
        <div class="flex items-baseline gap-3"><span class="price-new"><?php echo formatPrice($product['price']); ?></span><?php if($discount>0): ?><span class="text-sm text-[#9a8e81] line-through"><?php echo formatPrice($product['old_price']); ?></span><span class="pill px-2.5 py-1 text-xs bg-[#fdf1e0]">Tiết kiệm <?php echo formatPrice($saving); ?></span><?php endif; ?></div>
        <div class="text-xs text-[var(--muted)] mt-2">Giá đã gồm VAT · Gói giấy kraft · Thẻ bảo hành viết tay</div>
      </div>

      <div class="py-5 border-b border-[var(--line)] space-y-4">
        <div class="flex gap-2 text-xs">
          <span class="pill px-3 py-1.5 bg-[#eef3ea] border-[#d9e2d1]"><i class="fa-solid fa-leaf text-[var(--sage)] mr-1"></i> Tự nhiên</span>
          <span class="pill px-3 py-1.5"><i class="fa-solid fa-hammer text-[#9a8e81] mr-1"></i> Sửa được trọn đời</span>
          <span class="pill px-3 py-1.5"><i class="fa-solid fa-box-open text-[#9a8e81] mr-1"></i> Đổi 30 ngày</span>
        </div>
        <form method="post" class="space-y-4">
          <div><div class="text-sm font-medium mb-2">Số lượng</div><div class="flex items-center gap-3"><div class="flex items-center pill p-1 bg-white"><button type="button" onclick="decreaseQuantity()" class="w-9 h-9 rounded-full hover:bg-[var(--paper2)] grid place-items-center"><i class="fa-solid fa-minus text-xs"></i></button><input id="quantityInput" name="qty" value="1" min="1" max="<?php echo (int)$product['stock']; ?>" class="w-12 text-center bg-transparent outline-none text-sm font-semibold"><button type="button" onclick="increaseQuantity()" class="w-9 h-9 rounded-full hover:bg-[var(--paper2)] grid place-items-center"><i class="fa-solid fa-plus text-xs"></i></button></div><span class="text-xs text-[var(--muted)]">Tồn kho: <?php echo (int)$product['stock']; ?></span></div></div>
          <div class="grid grid-cols-[1.4fr_.6fr] gap-3">
            <button name="add_to_cart" <?php echo (int)$product['stock']<=0?'disabled':''; ?> class="btn-terra py-3.5 text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed"><i class="fa-solid fa-bag-shopping mr-2"></i>Thêm vào giỏ — <?php echo formatPrice($product['price']); ?></button>
            <a href="cart.php" class="btn-ghost py-3.5 text-sm font-semibold grid place-items-center">Xem giỏ</a>
          </div>
          <div class="flex items-center gap-2 text-xs text-[var(--muted)]"><i class="fa-solid fa-truck-fast"></i> Giao 1–3 ngày · Freeship đơn từ 500k · Kiểm tra trước khi nhận</div>
        </form>
      </div>

      <div class="pt-5 space-y-3 text-sm">
        <div class="flex gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs shrink-0"><i class="fa-solid fa-shield-halved"></i></span><div><div class="font-medium">Bảo hành 12 tháng</div><div class="text-xs text-[var(--muted)]">Nứt do nung, lỗi men — đổi mới. Hỗ trợ sửa tay nắm, nắp.</div></div></div>
        <div class="flex gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs shrink-0"><i class="fa-solid fa-pen-nib"></i></span><div><div class="font-medium">Khắc tên / gói quà</div><div class="text-xs text-[var(--muted)]">Ghi chú ở trang thanh toán, tụi mình viết tay.</div></div></div>
      </div>
    </div>
  </div>

  <div class="card mt-6">
    <div class="px-6 border-b border-[var(--line)] flex gap-6 overflow-x-auto">
      <button class="tab-btn active" onclick="switchTab(event,'description')">Mô tả & câu chuyện</button>
      <button class="tab-btn" onclick="switchTab(event,'specifications')">Thông số</button>
      <button class="tab-btn" onclick="switchTab(event,'reviews')">Đánh giá (<?php echo (int)$product['review_count']; ?>)</button>
    </div>
    <div class="p-6 md:p-8">
      <div id="description" class="tab-content">
        <div class="grid md:grid-cols-[1.6fr_.9fr] gap-8">
          <div class="prose max-w-none text-[14px] leading-6 text-[#3a332d]">
            <p><?php echo nl2br(htmlspecialchars($product['description']??'Chưa có mô tả.')); ?></p>
            <p class="mt-4 font-medium">Vì sao tụi mình chọn món này?</p>
            <ul class="list-disc pl-5 mt-2 space-y-1 text-[var(--muted)]">
              <li>Vật liệu tự nhiên, không chì — an tâm cho bếp gia đình.</li>
              <li>Làm tay, mỗi chiếc hơi khác nhau — đó là dấu tay người thợ.</li>
              <li>Dùng càng lâu càng đẹp: men lên nước, gỗ sẫm màu, gang bóng dần.</li>
            </ul>
            <div class="mt-6 bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4 flex gap-3"><span class="w-8 h-8 rounded-full bg-white border border-[var(--line)] grid place-items-center text-xs shrink-0">✎</span><div class="text-xs leading-5"><div class="font-semibold">Ghi chú của xưởng</div>“Đừng rửa bằng máy rửa chén với nước quá nóng. Lau khô sau khi rửa, đồ gốm sẽ bền hơn nhiều.”</div></div>
          </div>
          <div class="bg-[var(--paper2)] border border-[var(--line)] rounded-2xl p-5">
            <div class="text-xs tracking-[.14em] uppercase text-[var(--muted)]">Chăm sóc</div>
            <ul class="mt-3 space-y-2 text-sm leading-5 text-[#3a332d]">
              <li>• Rửa tay, lau khô — tránh sốc nhiệt.</li>
              <li>• Nồi gang: tôi dầu sau 2–3 lần dùng đầu.</li>
              <li>• Bát gốm: ngâm nước ấm trước khi dùng lần đầu.</li>
            </ul>
            <a href="term.php" class="inline-flex mt-4 text-xs font-semibold underline underline-offset-4">Chính sách bảo hành →</a>
          </div>
        </div>
      </div>
      <div id="specifications" class="tab-content hidden">
        <div class="overflow-hidden rounded-2xl border border-[var(--line)]">
          <table class="w-full text-sm"><tbody>
            <tr class="border-b border-[var(--line)] bg-[var(--paper)]"><td class="py-3 px-4 font-medium w-1/3">Mã</td><td class="py-3 px-4 text-[var(--muted)]">#<?php echo str_pad((string)$product['id'],5,'0',STR_PAD_LEFT); ?></td></tr>
            <tr class="border-b border-[var(--line)]"><td class="py-3 px-4 font-medium">Danh mục</td><td class="py-3 px-4 text-[var(--muted)]"><?php echo htmlspecialchars($product['cat_name']); ?></td></tr>
            <tr class="border-b border-[var(--line)] bg-[var(--paper)]"><td class="py-3 px-4 font-medium">Tồn kho</td><td class="py-3 px-4 text-[var(--muted)]"><?php echo (int)$product['stock']; ?> chiếc</td></tr>
            <tr class="border-b border-[var(--line)]"><td class="py-3 px-4 font-medium">Đánh giá</td><td class="py-3 px-4 text-[var(--muted)]"><?php echo $product['rating']; ?> / 5 · <?php echo (int)$product['review_count']; ?> lượt</td></tr>
            <tr><td class="py-3 px-4 font-medium">Bảo hành</td><td class="py-3 px-4 text-[var(--muted)]">12 tháng · sửa miễn phí</td></tr>
          </tbody></table>
        </div>
      </div>
      <div id="reviews" class="tab-content hidden">
        <div class="flex flex-wrap gap-6 p-5 bg-[var(--paper)] border border-[var(--line)] rounded-2xl">
          <div class="text-center min-w-[140px]"><div class="serif text-[40px] leading-none"><?php echo number_format((float)$product['rating'],1); ?></div><div class="flex justify-center gap-0.5 mt-1"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star text-xs <?php echo $i<=round($product['rating'])?'text-[#c9a14a]':'text-[#e7ddd0]'; ?>"></i><?php endfor; ?></div><div class="text-xs text-[var(--muted)] mt-1"><?php echo (int)$product['review_count']; ?> đánh giá</div></div>
          <div class="flex-1 min-w-[220px] space-y-1.5"><?php foreach([5,4,3,2,1] as $s): $w=max(8, min(60, $s*12)); ?><div class="flex items-center gap-2 text-xs"><span class="w-10 text-[var(--muted)]"><?php echo $s; ?> sao</span><div class="flex-1 h-2 bg-white border border-[var(--line)] rounded-full overflow-hidden"><div class="h-full bg-[#c9a14a]" style="width:<?php echo $w; ?>%"></div></div><span class="w-6 text-right text-[var(--muted)]"><?php echo $s*7; ?></span></div><?php endforeach; ?></div>
        </div>
        <div class="grid md:grid-cols-2 gap-3 mt-4 text-sm">
          <div class="card p-4"><div class="font-medium">Nguyễn Văn A <span class="text-xs text-[var(--muted)]">· 2 ngày trước · <span class="text-[#c9a14a]">★★★★★</span></span></div><div class="text-[var(--muted)] leading-5 mt-2">“Đóng gói rất có tâm, lót rơm, không một vết sứt. Cầm bát lên thấy ấm tay.”</div></div>
          <div class="card p-4"><div class="font-medium">Trần Thị B <span class="text-xs text-[var(--muted)]">· 1 tuần trước · <span class="text-[#c9a14a]">★★★★☆</span></span></div><div class="text-[var(--muted)] leading-5 mt-2">“Men đẹp, màu lên tự nhiên. Sẽ mua thêm bộ ấm.”</div></div>
        </div>
      </div>
    </div>
  </div>

  <?php if($relatedProducts): ?>
  <div class="mt-8">
    <div class="flex items-end justify-between"><h2 class="serif text-[24px]">Bạn có thể thích</h2><a href="shop.php?cat=<?php echo htmlspecialchars($product['cat_slug']); ?>" class="hidden md:inline-flex text-sm font-medium underline underline-offset-8">Xem thêm <?php echo htmlspecialchars($product['cat_name']); ?> →</a></div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
      <?php foreach($relatedProducts as $rp): $d=0; if(!empty($rp['old_price']) && $rp['old_price']>$rp['price']) $d=round((1-$rp['price']/$rp['old_price'])*100); ?>
      <a href="product-details.php?id=<?php echo $rp['id']; ?>" class="product-card">
        <div class="img-wrap h-[160px] grid place-items-center relative overflow-hidden"><?php if(!empty($rp['image'])): ?><img src="<?php echo htmlspecialchars($rp['image']); ?>" class="w-full h-full object-cover"><?php else: ?><span class="text-3xl">🍶</span><?php endif; ?><?php if($d>0): ?><span class="absolute top-3 left-3 bg-[var(--ink)] text-white text-[11px] font-bold px-2.5 py-1 rounded-full">-<?php echo $d; ?>%</span><?php endif; ?></div>
        <div class="p-4"><div class="font-medium leading-tight line-clamp-2 text-sm"><?php echo htmlspecialchars($rp['name']); ?></div><div class="flex items-center gap-1 mt-2"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star text-[10px] <?php echo $i<=round($rp['rating'])?'text-[#c9a14a]':'text-[#e7ddd0]'; ?>"></i><?php endfor; ?><span class="text-xs text-[var(--muted)] ml-1"><?php echo (int)$rp['review_count']; ?></span></div><div class="mt-3"><span class="price-new text-[16px]"><?php echo formatPrice($rp['price']); ?></span><?php if($d>0): ?><span class="price-old ml-1"><?php echo formatPrice($rp['old_price']); ?></span><?php endif; ?></div></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function changeMainImage(el){ const src=el.querySelector('img')?.src; if(src) document.querySelector('#mainImage img').src=src; document.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active')); el.classList.add('active'); }
function increaseQuantity(){ const i=document.getElementById('quantityInput'); i.value=parseInt(i.value||1)+1; }
function decreaseQuantity(){ const i=document.getElementById('quantityInput'); if(parseInt(i.value)>1) i.value=parseInt(i.value)-1; }
function switchTab(e,tab){ document.querySelectorAll('.tab-content').forEach(c=>{ c.classList.add('hidden'); }); document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active')); document.getElementById(tab).classList.remove('hidden'); e.currentTarget.classList.add('active'); }
document.getElementById('headerSearchBtn')?.addEventListener('click',()=>{ const v=document.getElementById('headerSearchInput').value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); });
document.getElementById('headerSearchInput')?.addEventListener('keypress',e=>{ if(e.key==='Enter'){ const v=e.target.value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v); }});
</script>
</body>
</html>
