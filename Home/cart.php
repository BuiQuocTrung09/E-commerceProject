<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $pid=(int)($_POST['product_id']??0);
    if($action==='update' && $pid>0){
        $qty=(int)($_POST['qty']??1);
        if($qty<=0){ unset($_SESSION['cart'][$pid]); }
        else {
            $st=$pdo->prepare("SELECT stock FROM products WHERE id=?");
            $st->execute([$pid]);
            $row=$st->fetch();
            if($row) $qty=min($qty,(int)$row['stock']);
            $_SESSION['cart'][$pid]=$qty;
        }
        header('Location: cart.php'); exit;
    }
    if($action==='remove' && $pid>0){
        unset($_SESSION['cart'][$pid]);
        header('Location: cart.php'); exit;
    }
    if($action==='apply_promo'){
        $code=strtoupper(trim($_POST['promo_code']??''));
        $map=['SAVE50'=>50000,'DISCOUNT20'=>100000,'WELCOME'=>30000,'VIP10'=>200000];
        if(isset($map[$code])){
            $_SESSION['promo_code']=$code;
            $_SESSION['promo_discount']=$map[$code];
            $_SESSION['promo_msg']="Đã áp dụng mã $code — giảm ".number_format($map[$code],0,',','.')."đ";
        } else {
            $_SESSION['promo_msg']='Mã không hợp lệ. Thử: SAVE50, DISCOUNT20, WELCOME, VIP10';
            unset($_SESSION['promo_code'],$_SESSION['promo_discount']);
        }
        header('Location: cart.php'); exit;
    }
}
if(isset($_GET['remove_promo'])){
    unset($_SESSION['promo_code'],$_SESSION['promo_discount'],$_SESSION['promo_msg']);
    header('Location: cart.php'); exit;
}
$cart = $_SESSION['cart'] ?? [];
$promoDiscount = (int)($_SESSION['promo_discount']??0);
$promoCode = $_SESSION['promo_code']??'';
$promoMsg = $_SESSION['promo_msg']??'';
unset($_SESSION['promo_msg']);
$displayName=getDisplayName();
$cartCount=getCartCount();
$items=[]; $subtotal=0;
if(!empty($cart)){
    $ids=array_keys($cart);
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $stmt=$pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $map=[]; foreach($rows as $r) $map[$r['id']]=$r;
    foreach($cart as $pid=>$qty){
        if(!isset($map[$pid])) continue;
        $p=$map[$pid];
        $p['qty']=$qty;
        $p['line_total']=(float)$p['price']*$qty;
        $subtotal+=$p['line_total'];
        $items[]=$p;
    }
}
$FREE_THRESHOLD=500000;
$SHIPPING=30000;
$shipping = $subtotal>= $FREE_THRESHOLD || $subtotal==0 ? 0 : $SHIPPING;
$total = max(0,$subtotal + $shipping - $promoDiscount);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Giỏ hàng — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--terracotta2:#a84522;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
body{background:var(--paper);color:var(--ink)}
.header{background:rgba(253,248,241,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.topbar{background:var(--ink);color:#f5ece0}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:var(--terracotta2);transform:translateY(-1px);box-shadow:0 8px 20px rgba(196,91,47,.18)}
.btn-ghost{border:1px solid var(--line);background:white;border-radius:999px}
.card{background:white;border:1px solid var(--line);border-radius:22px}
.qty-pill{border:1px solid var(--line);background:white;border-radius:999px;display:flex;align-items:center;padding:4px}
.qty-btn{width:32px;height:32px;border-radius:999px;display:grid;place-items:center;font-size:12px}
.qty-btn:hover{background:var(--paper2)}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between"><span class="hidden md:inline">Gói giấy kraft — không nilon</span><span>Giỏ hàng · Kiểm tra trước khi nhận</span><a href="shop.php" class="hidden md:inline hover:text-white/70">Tiếp tục chọn đồ →</a></div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold">cg</span><span class="serif text-[20px] font-bold">Chợ Gia Dụng</span></a>
    <nav class="hidden md:flex items-center gap-6 text-sm ml-6"><a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="about.php" class="hover:text-black/60">Câu chuyện</a></nav>
    <div class="hidden md:flex items-center gap-2 ml-auto pill h-[40px] px-4 text-sm"><i class="fa-regular fa-user"></i><span><?php echo $displayName; ?></span><?php if(isLoggedIn()): ?><a href="logout.php" class="text-[var(--terracotta)] ml-2 font-medium hover:underline">Đăng xuất</a><?php else: ?><a href="login.php" class="ml-2 underline underline-offset-4">Đăng nhập</a><?php endif; ?></div>
    <a href="cart.php" class="relative pill h-[40px] px-4 flex items-center gap-2 text-sm font-medium ml-auto md:ml-2 bg-[var(--ink)] text-white border-[var(--ink)]"><i class="fa-solid fa-bag-shopping"></i><span>Giỏ</span><span class="bg-white text-[var(--ink)] text-[11px] font-bold min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
  </div>
</header>

<div class="max-w-[1280px] mx-auto px-4 py-4 text-[13px] text-[var(--muted)] flex items-center gap-2">
  <a href="index.php" class="hover:text-[var(--ink)]">Trang chủ</a><span class="opacity-40">/</span><a href="shop.php" class="hover:text-[var(--ink)]">Chợ</a><span class="opacity-40">/</span><span class="text-[var(--ink)] font-medium">Giỏ hàng</span>
  <span class="ml-auto hidden md:inline-flex items-center gap-2 pill px-3 py-1 text-xs"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> Giao 1–3 ngày</span>
</div>

<div class="max-w-[1280px] mx-auto px-4 pb-12">
  <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
      <h1 class="serif text-[32px] md:text-[40px] leading-none">Giỏ của bạn <span class="text-[15px] font-sans font-normal text-[var(--muted)] tracking-normal">— <?php echo count($items); ?> món</span></h1>
      <p class="text-sm text-[var(--muted)] mt-2">Kiểm tra kỹ trước khi đặt — tụi mình gói từng món bằng giấy kraft và lót rơm.</p>
    </div>
    <div class="hidden md:flex items-center gap-2 text-sm"><a href="shop.php" class="btn-ghost px-5 py-2.5">Tiếp tục chọn đồ</a><?php if(!empty($items)): ?><a href="checkout.php" class="btn-terra px-6 py-2.5 font-semibold">Thanh toán →</a><?php endif; ?></div>
  </div>

  <?php if($promoMsg): ?><div class="mb-4 px-4 py-3 rounded-2xl text-sm border <?php echo $promoCode?'bg-[#eef3ea] border-[#cde0c7] text-[#2e5937]':'bg-[#fdf0e8] border-[#f0d0c0] text-[#7a3a20]'; ?>"><?php echo htmlspecialchars($promoMsg); ?> <?php if($promoCode): ?><a href="cart.php?remove_promo=1" class="underline ml-2">Xóa mã</a><?php endif; ?></div><?php endif; ?>

  <div class="grid lg:grid-cols-[1.7fr_.9fr] gap-6 items-start">
    <div class="card overflow-hidden">
      <?php if(empty($items)): ?>
        <div class="py-16 px-6 text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xl">🧺</div>
          <div class="serif text-[22px] mt-4">Giỏ còn trống</div>
          <div class="text-sm text-[var(--muted)] mt-2 max-w-[36ch] mx-auto">Bạn chưa chọn món nào. Thử dạo một vòng chợ — tụi mình vừa về lô gốm men tro mới.</div>
          <a href="shop.php" class="btn-terra inline-flex px-7 py-3 text-sm font-semibold mt-6">Dạo chợ ngay</a>
          <div class="text-xs text-[var(--muted)] mt-3">Gợi ý: Bát cơm men lam · Nồi gang 20cm</div>
        </div>
      <?php else: ?>
        <div class="divide-y divide-[var(--line)]">
          <?php foreach($items as $it): ?>
          <div class="p-4 md:p-5 flex gap-4">
            <a href="product-details.php?id=<?php echo $it['id']; ?>" class="w-[96px] h-[96px] md:w-[112px] md:h-[112px] bg-[var(--paper2)] border border-[var(--line)] rounded-2xl grid place-items-center shrink-0 overflow-hidden">
              <?php if(!empty($it['image'])): ?><img src="<?php echo htmlspecialchars($it['image']); ?>" class="w-full h-full object-cover"><?php else: ?><span class="text-3xl">🍶</span><?php endif; ?>
            </a>
            <div class="flex-1 min-w-0">
              <a href="product-details.php?id=<?php echo $it['id']; ?>" class="font-medium leading-tight line-clamp-2 hover:text-[var(--terracotta)]"><?php echo htmlspecialchars($it['name']); ?></a>
              <div class="text-xs text-[var(--muted)] mt-1"><?php echo htmlspecialchars($it['cat_name']??'Thủ công'); ?> · Còn <?php echo (int)$it['stock']; ?> · <span class="inline-flex items-center gap-1"><i class="fa-solid fa-star text-[10px] text-[#c9a14a]"></i><?php echo number_format((float)$it['rating'],1); ?></span></div>
              <div class="flex items-baseline gap-2 mt-2">
                <span class="font-semibold" style="font-family:'Fraunces',serif"><?php echo formatPrice($it['price']); ?></span>
                <?php if(!empty($it['old_price']) && $it['old_price']>$it['price']): ?><span class="text-xs text-[#9a8e81] line-through"><?php echo formatPrice($it['old_price']); ?></span><?php endif; ?>
                <span class="ml-auto md:hidden font-semibold"><?php echo formatPrice($it['line_total']); ?></span>
              </div>
              <div class="flex items-center gap-2 mt-3">
                <form method="post" class="qty-pill">
                  <input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>">
                  <button type="submit" name="qty" value="<?php echo max(1,(int)$it['qty']-1); ?>" class="qty-btn hover:bg-[var(--paper2)]"><i class="fa-solid fa-minus"></i></button>
                  <input type="number" name="qty" value="<?php echo (int)$it['qty']; ?>" min="1" max="<?php echo (int)$it['stock']; ?>" class="w-10 text-center bg-transparent outline-none text-sm font-semibold">
                  <button type="submit" name="qty" value="<?php echo (int)$it['qty']+1; ?>" class="qty-btn hover:bg-[var(--paper2)]"><i class="fa-solid fa-plus"></i></button>
                </form>
                <span class="hidden md:inline text-xs text-[var(--muted)]">Cập nhật tự động khi đổi số lượng</span>
                <form method="post" class="ml-auto hidden md:block"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>"><button class="text-xs text-[var(--muted)] hover:text-[#7a3a20] underline underline-offset-4">Xóa</button></form>
                <form method="post" class="md:hidden ml-auto"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>"><button class="w-9 h-9 rounded-full border border-[var(--line)] grid place-items-center text-xs"><i class="fa-regular fa-trash-can"></i></button></form>
              </div>
            </div>
            <div class="hidden md:block text-right min-w-[110px]">
              <div class="serif text-[18px] font-semibold"><?php echo formatPrice($it['line_total']); ?></div>
              <div class="text-xs text-[var(--muted)]"><?php echo (int)$it['qty']; ?> × <?php echo formatPrice($it['price']); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="bg-[var(--paper)] border-t border-[var(--line)] px-4 py-3 flex items-center justify-between text-sm">
          <a href="shop.php" class="hover:underline underline-offset-4"><i class="fa-solid fa-arrow-left text-xs mr-2"></i>Tiếp tục chọn đồ</a>
          <span class="text-xs text-[var(--muted)] hidden md:inline">Gói giấy kraft · Không nilon · Kèm thiệp viết tay nếu bạn ghi chú</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-6 sticky top-[88px]">
      <h3 class="serif text-[20px]">Tóm tắt</h3>
      <div class="mt-4 space-y-3 text-sm">
        <div class="flex justify-between"><span class="text-[var(--muted)]">Tạm tính</span><span class="font-medium"><?php echo formatPrice($subtotal); ?></span></div>
        <div class="flex justify-between"><span class="text-[var(--muted)]">Vận chuyển</span><span class="font-medium"><?php echo $shipping>0?formatPrice($shipping):'<span class="text-[#2e7d32]">Miễn phí</span>'; ?></span></div>
        <?php if($shipping>0): ?><div class="text-xs text-[var(--muted)] -mt-2">Miễn phí khi đơn từ <?php echo formatPrice($FREE_THRESHOLD); ?> — bạn còn thiếu <?php echo formatPrice(max(0,$FREE_THRESHOLD-$subtotal)); ?></div><?php endif; ?>
        <?php if($promoDiscount>0): ?><div class="flex justify-between text-[#2e5937]"><span>Mã <?php echo htmlspecialchars($promoCode); ?></span><span class="font-semibold">-<?php echo formatPrice($promoDiscount); ?></span></div><?php endif; ?>
        <div class="h-px bg-[var(--line)]"></div>
        <div class="flex justify-between items-baseline"><span class="font-medium">Tổng</span><span class="serif text-[24px] font-bold"><?php echo formatPrice($total); ?></span></div>
        <div class="text-xs text-[var(--muted)]">Đã gồm VAT · Thanh toán khi nhận hàng (COD) hoặc chuyển khoản</div>
      </div>

      <form method="post" class="mt-5">
        <input type="hidden" name="action" value="apply_promo">
        <label class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Mã ưu đãi</label>
        <div class="mt-2 flex gap-2">
          <input type="text" name="promo_code" value="<?php echo htmlspecialchars($promoCode); ?>" placeholder="Nhập SAVE50, WELCOME..." class="flex-1 pill h-[42px] px-4 text-sm outline-none bg-white focus:border-[#d8cabd]">
          <button class="pill h-[42px] px-5 text-sm font-medium bg-[var(--ink)] text-white border-[var(--ink)] hover:bg-black">Áp dụng</button>
        </div>
        <div class="text-xs text-[var(--muted)] mt-2">Thử: <span class="font-medium text-[var(--ink)]">SAVE50</span> · DISCOUNT20 · WELCOME · VIP10</div>
      </form>

      <?php if(!empty($items)): ?>
        <a href="checkout.php" class="btn-terra w-full mt-6 py-3.5 text-sm font-semibold flex items-center justify-center gap-2">Thanh toán — <?php echo formatPrice($total); ?> <i class="fa-solid fa-arrow-right text-xs"></i></a>
        <div class="text-xs text-center text-[var(--muted)] mt-2">Bảo mật · Đổi trả 30 ngày</div>
      <?php else: ?>
        <button disabled class="w-full mt-6 py-3.5 rounded-full bg-[#e7ddd0] text-[#9a8e81] text-sm font-semibold cursor-not-allowed">Giỏ trống — chưa thể thanh toán</button>
      <?php endif; ?>

      <div class="mt-6 bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4">
        <div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Yên tâm mua sắm</div>
        <ul class="mt-3 space-y-2 text-sm leading-5">
          <li class="flex gap-2"><i class="fa-solid fa-check text-[#2e7d32] mt-1 text-xs"></i><span><span class="font-medium">Kiểm tra trước khi nhận</span> — không ưng gửi lại ngay.</span></li>
          <li class="flex gap-2"><i class="fa-solid fa-check text-[#2e7d32] mt-1 text-xs"></i><span><span class="font-medium">Sửa miễn phí</span> — nứt men, lỏng quai.</span></li>
          <li class="flex gap-2"><i class="fa-solid fa-check text-[#2e7d32] mt-1 text-xs"></i><span><span class="font-medium">Gói quà</span> — ghi lời nhắn ở bước thanh toán.</span></li>
        </ul>
      </div>
    </div>
  </div>
</div>
</body>
</html>
