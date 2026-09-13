<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(empty($_SESSION['cart'])){ header('Location: cart.php'); exit; }
if(!isLoggedIn()){ header('Location: login.php?checkout=1&redirect='.urlencode('checkout.php')); exit; }
$userId=(int)$_SESSION['user_id'];
$uStmt=$pdo->prepare("SELECT * FROM users WHERE id=?");
$uStmt->execute([$userId]);
$user=$uStmt->fetch(PDO::FETCH_ASSOC);
$cart=$_SESSION['cart'];
$ids=array_keys($cart);
$placeholders=implode(',',array_fill(0,count($ids),'?'));
$stmt=$pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$map=[]; foreach($rows as $r) $map[$r['id']]=$r;
$items=[]; $subtotal=0;
foreach($cart as $pid=>$qty){
    if(!isset($map[$pid])) continue;
    $p=$map[$pid];
    $qty=min($qty,(int)$p['stock']);
    if($qty<=0) continue;
    $line=(float)$p['price']*$qty;
    $subtotal+=$line;
    $items[]=['id'=>$pid,'name'=>$p['name'],'price'=>(float)$p['price'],'qty'=>$qty,'line'=>$line];
}
if(empty($items)){ $_SESSION['cart']=[]; header('Location: cart.php'); exit; }
$promoDiscount=(int)($_SESSION['promo_discount']??0);
$error='';
$shippingCosts=['standard'=>30000,'express'=>50000,'same-day'=>80000];
$FREE_THRESHOLD=500000;
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])){
    $firstName=trim($_POST['firstName']??$user['first_name']);
    $lastName=trim($_POST['lastName']??$user['last_name']);
    $email=trim($_POST['email']??$user['email']);
    $phone=trim($_POST['phone']??$user['phone']);
    $address=trim($_POST['address']??$user['address']);
    $city=trim($_POST['city']??'');
    $district=trim($_POST['district']??'');
    $shippingMethod=$_POST['shipping']??'standard';
    $paymentMethod=$_POST['payment']??'cod';
    $notes=trim($_POST['notes']??'');
    $terms=isset($_POST['terms']);
    if(!isset($shippingCosts[$shippingMethod])) $shippingMethod='standard';
    $allowedPayments=['credit-card','ewallet','bank','cod'];
    if(!in_array($paymentMethod,$allowedPayments)) $paymentMethod='cod';
    if($firstName===''||$lastName===''||$email===''||$phone===''||$address===''||$city===''){
        $error='Vui lòng điền đầy đủ thông tin giao hàng.';
    } elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error='Email không hợp lệ.';
    } elseif(!preg_match('/^[0-9]{9,11}$/',$phone)){
        $error='Số điện thoại không hợp lệ (9–11 số).';
    } elseif(!$terms){
        $error='Bạn cần đồng ý với điều khoản và chính sách.';
    } else {
        $outOfStock=false;
        foreach($items as $it){
            $st=$pdo->prepare("SELECT stock FROM products WHERE id=?");
            $st->execute([$it['id']]);
            $s=(int)$st->fetchColumn();
            if($s < $it['qty']){ $error='“'.htmlspecialchars($it['name']).'” chỉ còn '.$s.' chiếc.'; $outOfStock=true; break; }
        }
        if(!$outOfStock){
            $shippingCost = $subtotal >= $FREE_THRESHOLD ? 0 : $shippingCosts[$shippingMethod];
            $total = max(0, $subtotal + $shippingCost - $promoDiscount);
            $fullAddress = $address . ($district?', '.$district:'') . ($city?', '.$city:'');
            try{
                $pdo->beginTransaction();
                $stmt=$pdo->prepare("INSERT INTO orders (user_id, total_price, shipping_address, phone, status, shipping_method, payment_method, notes) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$userId,$total,$fullAddress,$phone,'Pending',$shippingMethod,$paymentMethod,$notes]);
                $orderId=$pdo->lastInsertId();
                $stmtItem=$pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
                $stmtStock=$pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
                foreach($items as $it){
                    $stmtItem->execute([$orderId,$it['id'],$it['qty'],$it['price']]);
                    $stmtStock->execute([$it['qty'],$it['id']]);
                }
                $pdo->commit();
                unset($_SESSION['cart'],$_SESSION['promo_code'],$_SESSION['promo_discount'],$_SESSION['promo_msg']);
                header('Location: checkout.php?success=1&order_id='.$orderId);
                exit;
            }catch(PDOException $e){
                $pdo->rollBack();
                $error='Lỗi đặt hàng: '.$e->getMessage();
            }
        }
    }
}
if(isset($_GET['success']) && isset($_GET['order_id'])){
    $orderId=(int)$_GET['order_id'];
    $chk=$pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $chk->execute([$orderId,$userId]);
    $order=$chk->fetch(PDO::FETCH_ASSOC);
    if($order){
        $itStmt=$pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE order_id=?");
        $itStmt->execute([$orderId]);
        $orderItems=$itStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
<!doctype html><html lang="vi"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/><title>Đặt hàng thành công</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/><style>:root{--paper:#fdf8f1;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f}*{font-family:'Inter',sans-serif}.serif{font-family:'Fraunces',serif}</style></head><body style="background:var(--paper)">
<div class="max-w-[720px] mx-auto px-4 py-10">
  <div class="bg-white border border-[var(--line)] rounded-[24px] p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-[#eef3ea] border border-[#cde0c7] grid place-items-center mx-auto text-[#2e5937] text-xl"><i class="fa-solid fa-check"></i></div>
    <h1 class="serif text-[30px] mt-4">Cảm ơn bạn — đơn đã nhận!</h1>
    <p class="text-sm text-[var(--muted)] mt-2">Mã đơn <span class="font-mono font-bold text-[var(--ink)]">#<?php echo str_pad($orderId,6,'0',STR_PAD_LEFT); ?></span> · Tụi mình sẽ gói bằng giấy kraft và gửi trong 24h.</p>
    <div class="text-left bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-5 mt-6 text-sm">
      <div class="font-semibold">Chi tiết</div>
      <?php foreach($orderItems as $oi): ?><div class="flex justify-between py-2 border-b border-dashed border-[var(--line)] last:border-0"><span><?php echo htmlspecialchars($oi['name']); ?> × <?php echo (int)$oi['quantity']; ?></span><span class="font-medium"><?php echo number_format((float)$oi['price']*(int)$oi['quantity'],0,',','.'); ?>đ</span></div><?php endforeach; ?>
      <div class="flex justify-between font-bold pt-3"><span>Tổng</span><span class="serif text-lg"><?php echo number_format((float)$order['total_price'],0,',','.'); ?>đ</span></div>
      <div class="text-xs text-[var(--muted)] mt-3">Địa chỉ: <?php echo htmlspecialchars($order['shipping_address']); ?><br>Thanh toán: <?php echo htmlspecialchars($order['payment_method']); ?> · Vận chuyển: <?php echo htmlspecialchars($order['shipping_method']); ?></div>
    </div>
    <div class="flex gap-3 justify-center mt-6"><a href="shop.php" class="px-6 py-3 bg-[var(--ink)] text-white rounded-full text-sm font-semibold">Tiếp tục dạo chợ</a><a href="index.php" class="px-6 py-3 bg-white border border-[var(--line)] rounded-full text-sm font-semibold">Về trang chủ</a></div>
    <div class="text-xs text-[var(--muted)] mt-4">Đã gửi email xác nhận tới <?php echo htmlspecialchars($user['email']); ?> · Cần đổi gì, nhắn tụi mình nhé.</div>
  </div>
</div>
</body></html>
        <?php exit; }
}
$displayName=getDisplayName();
$cartCount=getCartCount();
$posted = $_POST ?? [];
$fNameVal = htmlspecialchars($posted['firstName'] ?? $user['first_name'] ?? '',ENT_QUOTES);
$lNameVal = htmlspecialchars($posted['lastName'] ?? $user['last_name'] ?? '',ENT_QUOTES);
$emailVal = htmlspecialchars($posted['email'] ?? $user['email'] ?? '',ENT_QUOTES);
$phoneVal = htmlspecialchars($posted['phone'] ?? $user['phone'] ?? '',ENT_QUOTES);
$addressVal = htmlspecialchars($posted['address'] ?? $user['address'] ?? '',ENT_QUOTES);
$cityVal = $posted['city'] ?? '';
$districtVal = $posted['district'] ?? '';
$shippingSel = $posted['shipping'] ?? 'standard';
$paymentSel = $posted['payment'] ?? 'cod';
$notesVal = htmlspecialchars($posted['notes'] ?? '',ENT_QUOTES);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Thanh toán — Chợ Gia Dụng</title>
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
.card{background:white;border:1px solid var(--line);border-radius:20px}
.input{border:1px solid var(--line);background:white;border-radius:999px;height:42px;padding:0 16px;outline:none;width:100%;font-size:14px}
.input:focus{border-color:#d8cabd;box-shadow:0 0 0 3px rgba(232,220,200,.5)}
.input-area{border:1px solid var(--line);background:white;border-radius:18px;padding:12px 16px;outline:none;width:100%;font-size:14px}
.input-area:focus{border-color:#d8cabd}
.radio-card{border:1px solid var(--line);background:white;border-radius:16px;padding:14px;display:flex;gap:12px;align-items:center;cursor:pointer}
.radio-card:has(input:checked){border-color:var(--ink);background:var(--paper)}
.pay-card{border:1px solid var(--line);background:white;border-radius:16px;padding:16px;text-align:center;cursor:pointer;position:relative}
.pay-card:has(input:checked){border-color:var(--ink);background:var(--paper)}
.pay-card input{position:absolute;opacity:0}
.sticky{position:sticky;top:88px}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-center gap-2"><span class="w-2 h-2 bg-[#7ec28a] rounded-full"></span> Thanh toán an toàn · Mã hóa · Không lưu thẻ</div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-4">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold">cg</span><span class="serif text-[18px] font-bold">Chợ Gia Dụng</span></a>
    <div class="hidden md:flex items-center gap-2 text-xs ml-6"><span class="w-7 h-7 rounded-full bg-[#eef3ea] border border-[#cde0c7] grid place-items-center text-[#2e5937]"><i class="fa-solid fa-check text-[10px]"></i></span> Giỏ hàng <span class="w-8 h-px bg-[var(--line)]"></span> <span class="w-7 h-7 rounded-full bg-[var(--ink)] text-white grid place-items-center text-xs">2</span> <span class="font-semibold">Thanh toán</span> <span class="w-8 h-px bg-[var(--line)]"></span> <span class="w-7 h-7 rounded-full bg-white border border-[var(--line)] grid place-items-center text-xs">3</span> <span class="text-[var(--muted)]">Hoàn tất</span></div>
    <div class="ml-auto flex items-center gap-2"><div class="relative group">
      <button class="hidden md:flex items-center gap-2 pill h-10 px-4 text-sm">
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
    </div><a href="cart.php" class="pill h-10 px-4 flex items-center gap-2 text-sm"><i class="fa-solid fa-arrow-left text-xs"></i><span class="hidden sm:inline">Quay lại giỏ</span><span class="bg-[var(--ink)] text-white text-xs min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a></div>
  </div>
</header>

<div class="max-w-[1280px] mx-auto px-4 py-6">
  <?php if($error): ?><div class="bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm mb-6"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <div class="grid lg:grid-cols-[1.65fr_.85fr] gap-6 items-start">
    <form method="post" class="space-y-5">
      <div class="card p-6">
        <div class="flex items-center gap-3"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-location-dot"></i></span><h2 class="font-semibold">Giao tới đâu?</h2><span class="ml-auto text-xs pill px-3 py-1">Giao 1–3 ngày · Freeship 500k</span></div>
        <div class="grid grid-cols-2 gap-3 mt-5">
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Họ</label><input name="firstName" value="<?php echo $fNameVal; ?>" class="input mt-1" required></div>
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Tên</label><input name="lastName" value="<?php echo $lNameVal; ?>" class="input mt-1" required></div>
          <div class="col-span-2"><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Email</label><input type="email" name="email" value="<?php echo $emailVal; ?>" class="input mt-1" required></div>
          <div class="col-span-2"><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Điện thoại</label><input name="phone" value="<?php echo $phoneVal; ?>" class="input mt-1" required></div>
          <div class="col-span-2"><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Địa chỉ (số nhà, đường)</label><input name="address" value="<?php echo $addressVal; ?>" placeholder="Ví dụ: 12 Nguyễn Trãi, P. Bến Thành" class="input mt-1" required></div>
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Tỉnh / Thành</label><select name="city" class="input mt-1" required><option value="">Chọn</option><option value="Hà Nội" <?php echo $cityVal==='Hà Nội'?'selected':''; ?>>Hà Nội</option><option value="TP. Hồ Chí Minh" <?php echo $cityVal==='TP. Hồ Chí Minh'?'selected':''; ?>>TP. Hồ Chí Minh</option><option value="Đà Nẵng" <?php echo $cityVal==='Đà Nẵng'?'selected':''; ?>>Đà Nẵng</option><option value="Hải Phòng" <?php echo $cityVal==='Hải Phòng'?'selected':''; ?>>Hải Phòng</option><option value="Cần Thơ" <?php echo $cityVal==='Cần Thơ'?'selected':''; ?>>Cần Thơ</option><option value="Khác" <?php echo $cityVal==='Khác'?'selected':''; ?>>Khác</option></select></div>
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Quận / Huyện</label><select name="district" class="input mt-1"><option value="">Chọn (không bắt buộc)</option><option value="Quận 1" <?php echo $districtVal==='Quận 1'?'selected':''; ?>>Quận 1</option><option value="Quận 2" <?php echo $districtVal==='Quận 2'?'selected':''; ?>>Quận 2</option><option value="Quận 3" <?php echo $districtVal==='Quận 3'?'selected':''; ?>>Quận 3</option><option value="Quận khác" <?php echo $districtVal==='Quận khác'?'selected':''; ?>>Quận khác</option></select></div>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="font-semibold flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-truck-fast"></i></span> Vận chuyển</h2>
        <div class="mt-4 space-y-2">
          <label class="radio-card"><input type="radio" name="shipping" value="standard" <?php echo $shippingSel==='standard'?'checked':''; ?> onchange="updateShipping()" class="accent-[var(--ink)]"><div class="flex-1"><div class="text-sm font-medium">Tiêu chuẩn — 3–5 ngày</div><div class="text-xs text-[var(--muted)]"><?php echo $subtotal>=$FREE_THRESHOLD?'Miễn phí vì đơn ≥500k':'30.000đ · Miễn phí khi đơn ≥500k'; ?></div></div><span class="text-sm font-semibold"><?php echo $subtotal>=$FREE_THRESHOLD?'Miễn phí':'30.000đ'; ?></span></label>
          <label class="radio-card"><input type="radio" name="shipping" value="express" <?php echo $shippingSel==='express'?'checked':''; ?> onchange="updateShipping()" class="accent-[var(--ink)]"><div class="flex-1"><div class="text-sm font-medium">Nhanh — 1–2 ngày</div><div class="text-xs text-[var(--muted)]">Ưu tiên đóng gói</div></div><span class="text-sm font-semibold">50.000đ</span></label>
          <label class="radio-card"><input type="radio" name="shipping" value="same-day" <?php echo $shippingSel==='same-day'?'checked':''; ?> onchange="updateShipping()" class="accent-[var(--ink)]"><div class="flex-1"><div class="text-sm font-medium">Trong ngày (HCM/HN)</div><div class="text-xs text-[var(--muted)]">Trước 18h</div></div><span class="text-sm font-semibold">80.000đ</span></label>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="font-semibold flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs"><i class="fa-solid fa-wallet"></i></span> Thanh toán</h2>
        <div class="mt-4 grid grid-cols-2 gap-3">
          <label class="pay-card"><input type="radio" name="payment" value="cod" <?php echo $paymentSel==='cod'?'checked':''; ?>><div class="text-lg">📦</div><div class="text-sm font-medium mt-1">COD</div><div class="text-xs text-[var(--muted)]">Trả khi nhận</div></label>
          <label class="pay-card"><input type="radio" name="payment" value="bank" <?php echo $paymentSel==='bank'?'checked':''; ?>><div class="text-lg">🏦</div><div class="text-sm font-medium mt-1">Chuyển khoản</div><div class="text-xs text-[var(--muted)]">QR — xác nhận nhanh</div></label>
          <label class="pay-card"><input type="radio" name="payment" value="credit-card" <?php echo $paymentSel==='credit-card'?'checked':''; ?>><div class="text-lg">💳</div><div class="text-sm font-medium mt-1">Thẻ</div><div class="text-xs text-[var(--muted)]">Visa / Mastercard</div></label>
          <label class="pay-card"><input type="radio" name="payment" value="ewallet" <?php echo $paymentSel==='ewallet'?'checked':''; ?>><div class="text-lg">📱</div><div class="text-sm font-medium mt-1">Ví điện tử</div><div class="text-xs text-[var(--muted)]">MoMo / ZaloPay</div></label>
        </div>
        <div class="mt-4"><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Ghi chú / Lời nhắn gói quà (không bắt buộc)</label><textarea name="notes" rows="3" placeholder="Ví dụ: Giao giờ hành chính, ghi thiệp 'Chúc mừng tân gia'..." class="input-area mt-1"><?php echo $notesVal; ?></textarea></div>
      </div>

      <div class="card p-6">
        <label class="flex gap-3 text-sm"><input type="checkbox" id="terms" name="terms" required class="mt-0.5 accent-[var(--ink)]"><span>Tôi đồng ý với <a href="term.php" class="underline underline-offset-4 font-medium">điều khoản</a> và cho phép lưu thông tin để giao hàng.</span></label>
        <label class="flex gap-3 text-sm mt-3"><input type="checkbox" name="newsletter" class="mt-0.5 accent-[var(--ink)]"><span class="text-[var(--muted)]">Gửi mình bản tin mỗi tháng (công thức, cách chăm đồ gốm).</span></label>
        <button name="place_order" value="1" class="btn-terra w-full mt-5 py-3.5 text-sm font-semibold">Đặt hàng — giao trong 1–3 ngày</button>
        <div class="text-xs text-center text-[var(--muted)] mt-3"><i class="fa-solid fa-lock text-[10px] mr-1"></i> Thông tin được mã hóa · Không lưu thẻ</div>
      </div>
    </form>

    <div class="sticky">
      <div class="card p-6">
        <h3 class="serif text-[20px]">Đơn của bạn</h3>
        <div class="mt-4 divide-y divide-[var(--line)] max-h-[320px] overflow-auto pr-1 -mr-1">
          <?php foreach($items as $it): ?>
          <div class="py-3 flex gap-3"><div class="flex-1"><div class="text-sm font-medium leading-tight"><?php echo htmlspecialchars($it['name']); ?></div><div class="text-xs text-[var(--muted)]"><?php echo (int)$it['qty']; ?> × <?php echo number_format($it['price'],0,',','.'); ?>đ</div></div><div class="text-sm font-medium"><?php echo number_format($it['line'],0,',','.'); ?>đ</div></div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4 space-y-2 text-sm border-t border-[var(--line)] pt-4">
          <div class="flex justify-between"><span class="text-[var(--muted)]">Tạm tính</span><span class="font-medium"><?php echo number_format($subtotal,0,',','.'); ?>đ</span></div>
          <div class="flex justify-between"><span class="text-[var(--muted)]">Vận chuyển</span><span class="font-medium" id="shippingCost"></span></div>
          <?php if($promoDiscount>0): ?><div class="flex justify-between text-[#2e5937]"><span>Mã <?php echo htmlspecialchars($_SESSION['promo_code']??''); ?></span><span>-<?php echo number_format($promoDiscount,0,',','.'); ?>đ</span></div><?php endif; ?>
          <div class="flex justify-between items-baseline pt-3 border-t border-[var(--line)]"><span class="font-medium">Tổng</span><span class="serif text-[22px] font-bold" id="totalPrice"></span></div>
        </div>
        <div class="mt-4 bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4 text-xs leading-5 text-[var(--muted)]">
          <div class="font-semibold text-[var(--ink)]">Gói hàng của bạn sẽ gồm</div>
          <div class="mt-1">• Giấy kraft + rơm lót · Không nilon<br>• Thẻ bảo hành viết tay<br>• Hướng dẫn chăm đồ gốm/gang</div>
        </div>
        <a href="cart.php" class="mt-4 flex items-center justify-center gap-2 text-sm underline underline-offset-4"><i class="fa-solid fa-arrow-left text-xs"></i> Quay lại giỏ</a>
      </div>
    </div>
  </div>
</div>

<script>
const subtotal=<?php echo (int)$subtotal; ?>;
const promoDiscount=<?php echo (int)$promoDiscount; ?>;
const freeThreshold=<?php echo $FREE_THRESHOLD; ?>;
const shippingCosts={standard:30000,express:50000,'same-day':80000};
function formatPrice(v){ return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',minimumFractionDigits:0}).format(v); }
function updateShipping(){
  const m=document.querySelector('input[name="shipping"]:checked')?.value||'standard';
  let c=shippingCosts[m]||30000;
  if(subtotal>=freeThreshold) c=0;
  document.getElementById('shippingCost').textContent=c===0?'Miễn phí':formatPrice(c);
  document.getElementById('totalPrice').textContent=formatPrice(Math.max(0,subtotal+c-promoDiscount));
}
document.querySelectorAll('input[name="shipping"]').forEach(r=>r.addEventListener('change',updateShipping));
updateShipping();
</script>
</body>
</html>
