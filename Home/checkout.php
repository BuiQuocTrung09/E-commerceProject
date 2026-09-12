<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

if(empty($_SESSION['cart'])){
    header('Location: cart.php');
    exit;
}
if(!isLoggedIn()){
    header('Location: login.php?checkout=1&redirect='.urlencode('checkout.php'));
    exit;
}
$userId=(int)$_SESSION['user_id'];
// fetch user info
$uStmt=$pdo->prepare("SELECT * FROM users WHERE id=?");
$uStmt->execute([$userId]);
$user=$uStmt->fetch(PDO::FETCH_ASSOC);

// fetch cart items with prices
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
if(empty($items)){
    $_SESSION['cart']=[];
    header('Location: cart.php'); exit;
}

$promoDiscount=(int)($_SESSION['promo_discount']??0);
$error=''; $success='';

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
        $error='Số điện thoại không hợp lệ (9-11 số).';
    } elseif(!$terms){
        $error='Bạn phải đồng ý với điều khoản và điều kiện.';
    } else {
        // verify stock again
        $outOfStock=false;
        foreach($items as $it){
            $st=$pdo->prepare("SELECT stock FROM products WHERE id=?");
            $st->execute([$it['id']]);
            $s=(int)$st->fetchColumn();
            if($s < $it['qty']){ $error='Sản phẩm "'.htmlspecialchars($it['name']).'" chỉ còn '.$s.' sản phẩm.'; $outOfStock=true; break; }
        }
        if(!$outOfStock){
            $shippingCost = $subtotal >= $FREE_THRESHOLD ? 0 : $shippingCosts[$shippingMethod];
            // if promo discount exists, consider it; but cap so total not negative
            $total = max(0, $subtotal + $shippingCost - $promoDiscount);
            // if free shipping due to threshold, keep it
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
                // clear cart & promo
                unset($_SESSION['cart'],$_SESSION['promo_code'],$_SESSION['promo_discount'],$_SESSION['promo_msg']);
                $_SESSION['order_success']=$orderId;
                header('Location: checkout.php?success=1&order_id='.$orderId);
                exit;
            }catch(PDOException $e){
                $pdo->rollBack();
                $error='Lỗi đặt hàng: '.$e->getMessage();
            }
        }
    }
}

// success view
if(isset($_GET['success']) && isset($_GET['order_id'])){
    $orderId=(int)$_GET['order_id'];
    // verify belongs to user
    $chk=$pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $chk->execute([$orderId,$userId]);
    $order=$chk->fetch(PDO::FETCH_ASSOC);
    if($order){
        $itStmt=$pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE order_id=?");
        $itStmt->execute([$orderId]);
        $orderItems=$itStmt->fetchAll(PDO::FETCH_ASSOC);
        $displayName=getDisplayName();
        ?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Đặt hàng thành công</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>*{font-family:'Montserrat',sans-serif}</style></head>
<body class="bg-gray-50">
<div class="max-w-3xl mx-auto px-4 py-12 text-center">
    <div class="bg-white rounded-xl shadow-sm p-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-500 text-3xl"><i class="fas fa-check"></i></div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Đặt hàng thành công!</h1>
        <p class="text-gray-600 mb-2">Mã đơn hàng: <span class="font-mono font-bold text-red-500">#<?php echo str_pad($orderId,6,'0',STR_PAD_LEFT); ?></span></p>
        <p class="text-sm text-gray-500 mb-6">Chúng tôi đã gửi xác nhận tới <?php echo htmlspecialchars($user['email']); ?>. Vui lòng kiểm tra email.</p>
        <div class="text-left bg-gray-50 rounded-lg p-4 mb-6 text-sm">
            <div class="font-semibold mb-2">Chi tiết đơn hàng</div>
            <?php foreach($orderItems as $oi): ?><div class="flex justify-between py-1"><span><?php echo htmlspecialchars($oi['name']); ?> × <?php echo (int)$oi['quantity']; ?></span><span><?php echo number_format((float)$oi['price']*(int)$oi['quantity'],0,',','.'); ?>đ</span></div><?php endforeach; ?>
            <div class="border-t mt-2 pt-2 flex justify-between font-bold"><span>Tổng thanh toán</span><span class="text-red-500"><?php echo number_format((float)$order['total_price'],0,',','.'); ?>đ</span></div>
            <div class="mt-2 text-gray-600">Địa chỉ: <?php echo htmlspecialchars($order['shipping_address']); ?></div>
            <div class="text-gray-600">Thanh toán: <?php echo htmlspecialchars($order['payment_method']); ?> • Vận chuyển: <?php echo htmlspecialchars($order['shipping_method']); ?></div>
        </div>
        <div class="flex gap-3 justify-center">
            <a href="shop.php" class="px-6 py-3 bg-gray-900 text-white rounded-lg font-semibold hover:bg-black">Tiếp tục mua sắm</a>
            <a href="index.php" class="px-6 py-3 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50">Về trang chủ</a>
        </div>
    </div>
</div>
</body></html>
        <?php
        exit;
    }
}

$displayName=getDisplayName();
$cartCount=getCartCount();
$subtotalFmt=number_format($subtotal,0,',','.');
// keep posted values or defaults
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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Nhà Bếp & Đồ Gia Dụng Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{font-family:'Montserrat',sans-serif;}
        body{background:#f9f9f9;}
        .nav-link:hover{color:#ff6b6b;transition:color 0.3s ease;}
        .form-input{border:1px solid #e5e7eb;transition:all 0.3s ease;}
        .form-input:focus{outline:none;border-color:#ff6b6b;box-shadow:0 0 0 3px rgba(255,107,107,0.1);}
        .radio-option{cursor:pointer;border:1px solid #e5e7eb;transition:all 0.3s ease;position:relative;}
        .radio-option input[type="radio"]{accent-color:#ff6b6b;}
        .radio-option:has(input[type="radio"]:checked){border-color:#ff6b6b;background:#fff5f5;}
        .payment-card{cursor:pointer;border:1px solid #e5e7eb;transition:all 0.3s ease;position:relative;}
        .payment-card input[type="radio"]{accent-color:#ff6b6b;position:absolute;opacity:0;}
        .payment-card:has(input[type="radio"]:checked){border-color:#ff6b6b;background:#fff5f5;}
        .btn-checkout{background:#ff6b6b;transition:all 0.3s ease;}
        .btn-checkout:hover:not(:disabled){background:#e55555;box-shadow:0 8px 20px rgba(255,107,107,0.3);transform:translateY(-2px);}
        .btn-checkout:disabled{opacity:0.5;cursor:not-allowed;}
        .sticky-summary{position:sticky;top:20px;}
        .section-icon{color:#ff6b6b;font-size:18px;}
        @media(max-width:768px){.sticky-summary{position:static;top:auto;}}
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <a href="index.php" class="font-bold hover:opacity-80 transition flex items-center gap-2"><img src="../Pics/logo_nenden.png" alt="Logo" class="h-10"></a>
            <div class="hidden md:flex items-center gap-2 text-sm"><span class="text-gray-400">Thanh Toán • <?php echo $displayName; ?></span><a href="logout.php" class="text-red-400 hover:underline ml-2">Đăng xuất</a></div>
            <a href="cart.php" class="text-sm hover:text-red-500 transition flex items-center gap-2"><i class="fas fa-arrow-left"></i><span class="hidden sm:inline">Quay lại giỏ</span><span class="bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
        </nav>
    </header>

    <div class="bg-white border-b border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-center gap-2 sm:gap-4">
                <div class="flex flex-col items-center"><div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center mb-1 text-xs font-bold"><i class="fas fa-check"></i></div><span class="text-xs font-semibold text-gray-600">Giỏ hàng</span></div>
                <div class="w-6 sm:w-12 h-0.5 bg-red-500"></div>
                <div class="flex flex-col items-center"><div class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center mb-1 text-xs font-bold">2</div><span class="text-xs font-semibold text-red-500">Thanh toán</span></div>
                <div class="w-6 sm:w-12 h-0.5 bg-gray-300"></div>
                <div class="flex flex-col items-center"><div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center mb-1 text-xs font-bold">3</div><span class="text-xs font-semibold text-gray-600">Hoàn thành</span></div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <form method="post" id="checkoutForm">
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-3"><i class="fas fa-map-pin section-icon"></i> Thông tin giao hàng</h2>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Họ</label><input type="text" name="firstName" value="<?php echo $fNameVal; ?>" class="w-full px-3 py-2 form-input rounded-lg text-sm" required></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Tên</label><input type="text" name="lastName" value="<?php echo $lNameVal; ?>" class="w-full px-3 py-2 form-input rounded-lg text-sm" required></div>
                        </div>
                        <div class="mb-4"><label class="block text-sm font-semibold text-gray-700 mb-2">Email</label><input type="email" name="email" value="<?php echo $emailVal; ?>" class="w-full px-3 py-2 form-input rounded-lg text-sm" required></div>
                        <div class="mb-4"><label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại</label><input type="tel" name="phone" value="<?php echo $phoneVal; ?>" class="w-full px-3 py-2 form-input rounded-lg text-sm" required></div>
                        <div class="mb-4"><label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ</label><input type="text" name="address" value="<?php echo $addressVal; ?>" placeholder="123 Đường ABC, Quận 1" class="w-full px-3 py-2 form-input rounded-lg text-sm" required></div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Thành phố/Tỉnh</label><select name="city" class="w-full px-3 py-2 form-input rounded-lg text-sm" required><option value="">Chọn thành phố</option><option value="Hà Nội" <?php echo $cityVal==='Hà Nội'?'selected':''; ?>>Hà Nội</option><option value="TP. Hồ Chí Minh" <?php echo $cityVal==='TP. Hồ Chí Minh'?'selected':''; ?>>TP. Hồ Chí Minh</option><option value="Đà Nẵng" <?php echo $cityVal==='Đà Nẵng'?'selected':''; ?>>Đà Nẵng</option><option value="Hải Phòng" <?php echo $cityVal==='Hải Phòng'?'selected':''; ?>>Hải Phòng</option><option value="Cần Thơ" <?php echo $cityVal==='Cần Thơ'?'selected':''; ?>>Cần Thơ</option><option value="Khác" <?php echo $cityVal==='Khác'?'selected':''; ?>>Khác</option></select></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Quận/Huyện</label><select name="district" class="w-full px-3 py-2 form-input rounded-lg text-sm"><option value="">Chọn quận/huyện</option><option value="Quận 1" <?php echo $districtVal==='Quận 1'?'selected':''; ?>>Quận 1</option><option value="Quận 2" <?php echo $districtVal==='Quận 2'?'selected':''; ?>>Quận 2</option><option value="Quận 3" <?php echo $districtVal==='Quận 3'?'selected':''; ?>>Quận 3</option><option value="Quận khác" <?php echo $districtVal==='Quận khác'?'selected':''; ?>>Quận khác</option></select></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-3"><i class="fas fa-truck section-icon"></i> Phương thức vận chuyển</h2>
                        <div class="space-y-3">
                            <label class="radio-option rounded-lg p-4 flex items-center gap-3"><input type="radio" name="shipping" value="standard" <?php echo $shippingSel==='standard'?'checked':''; ?> onchange="updateShipping()"><div class="flex-1"><div class="font-semibold text-gray-900 text-sm">Vận chuyển tiêu chuẩn</div><div class="text-xs text-gray-600">3-5 ngày • <?php echo $subtotal>=$FREE_THRESHOLD?'Miễn phí (đơn ≥500k)':'30.000đ'; ?></div></div><div class="font-semibold text-gray-900 text-sm"><?php echo $subtotal>=$FREE_THRESHOLD?'Miễn phí':'30.000đ'; ?></div></label>
                            <label class="radio-option rounded-lg p-4 flex items-center gap-3"><input type="radio" name="shipping" value="express" <?php echo $shippingSel==='express'?'checked':''; ?> onchange="updateShipping()"><div class="flex-1"><div class="font-semibold text-gray-900 text-sm">Vận chuyển nhanh</div><div class="text-xs text-gray-600">1-2 ngày làm việc</div></div><div class="font-semibold text-gray-900 text-sm">50.000đ</div></label>
                            <label class="radio-option rounded-lg p-4 flex items-center gap-3"><input type="radio" name="shipping" value="same-day" <?php echo $shippingSel==='same-day'?'checked':''; ?> onchange="updateShipping()"><div class="flex-1"><div class="font-semibold text-gray-900 text-sm">Giao cùng ngày (HCM, Hà Nội)</div><div class="text-xs text-gray-600">Trước 18h cùng ngày</div></div><div class="font-semibold text-gray-900 text-sm">80.000đ</div></label>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-3"><i class="fas fa-wallet section-icon"></i> Phương thức thanh toán</h2>
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <label class="payment-card rounded-lg p-4 flex flex-col items-center text-center cursor-pointer"><input type="radio" name="payment" value="cod" <?php echo $paymentSel==='cod'?'checked':''; ?>><i class="fas fa-box text-2xl mb-2 text-gray-700"></i><span class="font-semibold text-xs text-gray-900">COD - Trả khi nhận</span></label>
                            <label class="payment-card rounded-lg p-4 flex flex-col items-center text-center cursor-pointer"><input type="radio" name="payment" value="bank" <?php echo $paymentSel==='bank'?'checked':''; ?>><i class="fas fa-building text-2xl mb-2 text-gray-700"></i><span class="font-semibold text-xs text-gray-900">Chuyển Khoản</span></label>
                            <label class="payment-card rounded-lg p-4 flex flex-col items-center text-center cursor-pointer"><input type="radio" name="payment" value="credit-card" <?php echo $paymentSel==='credit-card'?'checked':''; ?>><i class="fas fa-credit-card text-2xl mb-2 text-gray-700"></i><span class="font-semibold text-xs text-gray-900">Thẻ Tín Dụng</span></label>
                            <label class="payment-card rounded-lg p-4 flex flex-col items-center text-center cursor-pointer"><input type="radio" name="payment" value="ewallet" <?php echo $paymentSel==='ewallet'?'checked':''; ?>><i class="fas fa-mobile-alt text-2xl mb-2 text-gray-700"></i><span class="font-semibold text-xs text-gray-900">Ví Điện Tử</span></label>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ghi chú đơn hàng (tùy chọn)</label><textarea name="notes" placeholder="Ví dụ: giao giờ hành chính..." class="w-full px-3 py-2 form-input rounded-lg text-sm" rows="3"><?php echo $notesVal; ?></textarea>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="space-y-3 mb-6">
                            <div class="flex items-start gap-3"><input type="checkbox" id="terms" name="terms" required class="w-4 h-4 mt-1 rounded" style="accent-color:#ff6b6b;"><label for="terms" class="text-sm text-gray-600 cursor-pointer leading-tight">Tôi đồng ý với <a href="term.php" class="font-semibold underline">điều khoản và điều kiện</a> cũng như <span class="font-semibold">chính sách bảo mật</span></label></div>
                            <div class="flex items-start gap-3"><input type="checkbox" id="newsletter" name="newsletter" class="w-4 h-4 mt-1 rounded" style="accent-color:#ff6b6b;"><label for="newsletter" class="text-sm text-gray-600 cursor-pointer">Gửi cho tôi thông tin về ưu đãi và khuyến mãi</label></div>
                        </div>
                        <button type="submit" name="place_order" value="1" class="w-full btn-checkout text-white font-bold py-3 rounded-lg transition text-sm"><i class="fas fa-check-circle mr-2"></i>Đặt Hàng Ngay</button>
                        <p class="text-xs text-gray-500 text-center mt-3"><i class="fas fa-lock mr-1"></i> Thanh toán an toàn - Mã hóa SSL</p>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky-summary">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-list section-icon"></i> Tóm tắt đơn hàng</h3>
                    <div class="max-h-80 overflow-y-auto mb-4 pr-2 divide-y divide-gray-100">
                        <?php foreach($items as $it): ?>
                        <div class="py-3 flex justify-between items-center">
                            <div class="flex-1 pr-2"><div class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($it['name']); ?></div><div class="text-xs text-gray-500">Số lượng: <?php echo (int)$it['qty']; ?> × <?php echo number_format($it['price'],0,',','.'); ?>đ</div></div>
                            <div class="font-semibold text-gray-900 text-sm"><?php echo number_format($it['line'],0,',','.'); ?>đ</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-3 text-sm border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-gray-700"><span>Tạm tính</span><span id="subtotal" class="font-semibold"><?php echo number_format($subtotal,0,',','.'); ?>đ</span></div>
                        <div class="flex justify-between text-gray-700"><span>Vận chuyển</span><span id="shippingCost" class="font-semibold"></span></div>
                        <?php if($promoDiscount>0): ?><div class="flex justify-between text-green-600"><span>Giảm giá (<?php echo htmlspecialchars($_SESSION['promo_code']??''); ?>)</span><span class="font-semibold">-<?php echo number_format($promoDiscount,0,',','.'); ?>đ</span></div><?php endif; ?>
                        <div class="flex justify-between text-lg font-bold text-gray-900 pt-3 border-t border-gray-200"><span>Tổng cộng</span><span id="totalPrice" class="text-red-500"></span></div>
                        <?php if($promoDiscount>0): ?><div class="text-xs text-green-600">Đã áp dụng mã <?php echo htmlspecialchars($_SESSION['promo_code']); ?></div><?php endif; ?>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200"><div class="flex items-center justify-center gap-2 text-xs text-gray-500"><i class="fas fa-lock"></i><span>Thanh toán an toàn - Mã hóa SSL</span></div></div>
                    <a href="cart.php" class="mt-4 block text-center text-sm text-gray-600 hover:text-red-500"><i class="fas fa-arrow-left mr-1"></i> Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-[#1a1a1a] text-white py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div><h3 class="font-bold text-lg mb-4">Về Chúng Tôi</h3><p class="text-gray-400 text-sm">Chợ gia dụng lớn nhất Việt Nam với hàng ngàn sản phẩm chất lượng cao.</p></div>
                <div><h3 class="font-bold text-lg mb-4">Liên Kết Nhanh</h3><ul class="text-gray-400 text-sm space-y-2"><li><a href="index.php" class="hover:text-red-500">Trang chủ</a></li><li><a href="shop.php" class="hover:text-red-500">Sản phẩm</a></li></ul></div>
                <div><h3 class="font-bold text-lg mb-4">Hỗ Trợ</h3><ul class="text-gray-400 text-sm space-y-2"><li><a href="term.php" class="hover:text-red-500">Điều khoản dịch vụ</a></li></ul></div>
                <div><h3 class="font-bold text-lg mb-4">Theo Dõi</h3><div class="flex gap-4"><a href="#" class="bg-gray-700 hover:bg-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="fab fa-facebook-f"></i></a><a href="#" class="bg-gray-700 hover:bg-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="fab fa-instagram"></i></a></div></div>
            </div>
            <hr class="border-gray-700 mb-6"><div class="text-center text-gray-400 text-sm">&copy; 2024 Chợ Gia Dụng - Tất cả quyền được bảo lưu.</div>
        </div>
    </footer>

    <script>
        const subtotal=<?php echo (int)$subtotal; ?>;
        const promoDiscount=<?php echo (int)$promoDiscount; ?>;
        const freeThreshold=<?php echo $FREE_THRESHOLD; ?>;
        const shippingCosts={standard:30000,express:50000,'same-day':80000};
        function formatPrice(v){ return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',minimumFractionDigits:0}).format(v); }
        function updateShipping(){
            const method=document.querySelector('input[name="shipping"]:checked')?.value||'standard';
            let cost=shippingCosts[method]||30000;
            if(subtotal>=freeThreshold) cost=0;
            document.getElementById('shippingCost').textContent = cost===0 ? 'Miễn phí' : formatPrice(cost);
            const total=subtotal+cost-promoDiscount;
            document.getElementById('totalPrice').textContent=formatPrice(Math.max(0,total));
        }
        document.querySelectorAll('input[name="shipping"]').forEach(r=>r.addEventListener('change',updateShipping));
        updateShipping();
    </script>
</body>
</html>
