<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

// Handle actions
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $pid=(int)($_POST['product_id']??0);
    if($action==='update' && $pid>0){
        $qty=(int)($_POST['qty']??1);
        if($qty<=0){
            unset($_SESSION['cart'][$pid]);
        } else {
            // check stock
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
            $_SESSION['promo_msg']="Áp dụng thành công! Giảm ".number_format($map[$code],0,',','.')."đ";
        } else {
            $_SESSION['promo_msg']='Mã không hợp lệ. Thử: SAVE50, DISCOUNT20, WELCOME, VIP10';
            unset($_SESSION['promo_code']); unset($_SESSION['promo_discount']);
        }
        header('Location: cart.php'); exit;
    }
    if($action==='clear_promo'){
        unset($_SESSION['promo_code'],$_SESSION['promo_discount'],$_SESSION['promo_msg']);
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

// Fetch cart products
$items=[];
$subtotal=0;
if(!empty($cart)){
    $ids=array_keys($cart);
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $stmt=$pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    // map by id
    $map=[];
    foreach($rows as $r) $map[$r['id']]=$r;
    foreach($cart as $pid=>$qty){
        if(!isset($map[$pid])) continue;
        $p=$map[$pid];
        $p['qty']=$qty;
        $p['line_total']=(float)$p['price']*$qty;
        $subtotal+=$p['line_total'];
        $items[]=$p;
    }
}
$SHIPPING_COST=30000;
$FREE_THRESHOLD=500000;
$shipping = $subtotal>= $FREE_THRESHOLD || $subtotal==0 ? 0 : $SHIPPING_COST;
$total = max(0,$subtotal + $shipping - $promoDiscount);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Nhà Bếp & Đồ Gia Dụng Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{font-family:'Montserrat',sans-serif;box-sizing:border-box;}
        body{background:#f8f9fa;color:#333;}
        header{background:#1a1a1a;color:white;}
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2 font-bold text-lg"><img src="../Pics/logo_nenden.png" class="h-8"> Chợ Gia Dụng</a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="index.php" class="hover:text-red-400">Trang Chủ</a>
                <a href="shop.php" class="hover:text-red-400">Chợ</a>
                <a href="about.php" class="hover:text-red-400">Về chúng tôi</a>
                <div class="flex items-center gap-2">
                    <i class="fas fa-user"></i><span><?php echo $displayName; ?></span>
                    <?php if(isLoggedIn()): ?><a href="logout.php" class="text-red-400 ml-2 hover:underline">Đăng xuất</a><?php else: ?><a href="login.php" class="ml-2 hover:underline">Đăng nhập</a><?php endif; ?>
                </div>
            </nav>
            <a href="cart.php" class="flex items-center gap-2 relative"><i class="fas fa-shopping-cart text-xl"></i><span class="hidden sm:inline text-sm">Giỏ hàng</span><span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 flex items-center gap-3"><i class="fas fa-shopping-cart text-red-500"></i> Giỏ Hàng của Tôi <span class="text-lg font-normal text-gray-500">(<?php echo count($items); ?> sản phẩm)</span></h1>

        <?php if($promoMsg): ?><div class="mb-4 p-3 rounded-lg text-sm <?php echo $promoCode?'bg-green-50 border border-green-200 text-green-700':'bg-red-50 border border-red-200 text-red-700'; ?>"><?php echo htmlspecialchars($promoMsg); ?></div><?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                <?php if(empty($items)): ?>
                    <div class="py-16 text-center">
                        <div class="text-6xl mb-4">🛒</div>
                        <p class="text-gray-500 mb-2">Giỏ hàng của bạn trống</p>
                        <p class="text-sm text-gray-400 mb-6">Hãy khám phá các sản phẩm tuyệt vời của chúng tôi</p>
                        <a href="shop.php" class="inline-block bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold">Tiếp tục mua sắm</a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach($items as $it): ?>
                        <div class="p-4 md:p-6 flex gap-4 items-center">
                            <a href="product-details.php?id=<?php echo $it['id']; ?>" class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <?php if(!empty($it['image'])): ?><img src="<?php echo htmlspecialchars($it['image']); ?>" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-image text-2xl text-gray-400"></i><?php endif; ?>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="product-details.php?id=<?php echo $it['id']; ?>" class="font-semibold text-gray-900 hover:text-red-500 line-clamp-2"><?php echo htmlspecialchars($it['name']); ?></a>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($it['cat_name']??''); ?> • Còn <?php echo (int)$it['stock']; ?> sp</div>
                                <div class="text-red-500 font-bold mt-1"><?php echo formatPrice($it['price']); ?></div>
                                <?php if(!empty($it['old_price']) && $it['old_price']>$it['price']): ?><div class="text-xs text-gray-400 line-through"><?php echo formatPrice($it['old_price']); ?></div><?php endif; ?>
                            </div>
                            <form method="post" class="flex items-center gap-2">
                                <input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>">
                                <div class="flex items-center border rounded-lg overflow-hidden">
                                    <button type="submit" name="qty" value="<?php echo max(1,$it['qty']-1); ?>" formaction="cart.php" onclick="this.form.qty.value=parseInt(this.form.qty.value)-1; if(this.form.qty.value<1) this.form.qty.value=1;" class="px-3 py-2 hover:bg-gray-50"><i class="fas fa-minus text-xs"></i></button>
                                    <input type="number" name="qty" value="<?php echo (int)$it['qty']; ?>" min="1" max="<?php echo (int)$it['stock']; ?>" class="w-12 text-center border-x py-2 text-sm focus:outline-none">
                                    <button type="submit" class="px-3 py-2 hover:bg-gray-50"><i class="fas fa-plus text-xs"></i></button>
                                </div>
                                <button type="submit" class="hidden md:inline-flex px-3 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-black">Cập nhật</button>
                            </form>
                            <div class="hidden md:block text-right min-w-[100px]">
                                <div class="font-bold"><?php echo formatPrice($it['line_total']); ?></div>
                                <form method="post" class="mt-1"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>"><button type="submit" class="text-xs text-red-500 hover:underline"><i class="fas fa-trash mr-1"></i>Xóa</button></form>
                            </div>
                            <form method="post" class="md:hidden"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?php echo $it['id']; ?>"><button class="text-red-500 p-2"><i class="fas fa-trash"></i></button></form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-4 border-t bg-gray-50 flex justify-between items-center text-sm">
                        <a href="shop.php" class="text-gray-700 hover:text-red-500"><i class="fas fa-arrow-left mr-2"></i>Tiếp tục mua sắm</a>
                        <span class="text-gray-500">Cập nhật số lượng rồi nhấn “Cập nhật”</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 h-fit sticky top-20">
                <h3 class="font-bold text-lg mb-4">Tóm Tắt Đơn Hàng</h3>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded mb-4 text-sm text-blue-800"><i class="fas fa-truck mr-2"></i>Miễn phí vận chuyển cho đơn trên <?php echo formatPrice($FREE_THRESHOLD); ?></div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Tạm tính:</span><span class="font-semibold"><?php echo formatPrice($subtotal); ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Vận chuyển:</span><span class="font-semibold"><?php echo $shipping>0?formatPrice($shipping):'<span class="text-green-600">Miễn phí</span>'; ?></span></div>
                    <?php if($promoDiscount>0): ?><div class="flex justify-between text-green-600"><span>Giảm giá (<?php echo htmlspecialchars($promoCode); ?>):</span><span class="font-semibold">-<?php echo formatPrice($promoDiscount); ?></span></div><?php endif; ?>
                    <div class="flex justify-between text-lg font-bold border-t pt-3"><span>Tổng cộng:</span><span class="text-red-500"><?php echo formatPrice($total); ?></span></div>
                </div>

                <form method="post" class="mt-4 space-y-2">
                    <input type="hidden" name="action" value="apply_promo">
                    <div class="flex gap-2">
                        <input type="text" name="promo_code" placeholder="Nhập mã khuyến mãi" value="<?php echo htmlspecialchars($promoCode); ?>" class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-black">Áp dụng</button>
                    </div>
                    <div class="text-xs text-gray-500">Thử: SAVE50, DISCOUNT20, WELCOME, VIP10</div>
                    <?php if($promoCode): ?><a href="cart.php?remove_promo=1" class="text-xs text-red-500 hover:underline">Xóa mã</a><?php endif; ?>
                </form>

                <?php if(!empty($items)): ?>
                    <a href="checkout.php" class="mt-6 block w-full text-center bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-bold transition">Tiếp Tục Thanh Toán <i class="fas fa-arrow-right ml-2"></i></a>
                <?php else: ?>
                    <button disabled class="mt-6 w-full bg-gray-300 text-white py-3 rounded-lg font-bold cursor-not-allowed">Giỏ hàng trống</button>
                <?php endif; ?>

                <div class="mt-6 pt-4 border-t text-xs text-gray-500 space-y-1">
                    <div class="font-semibold text-gray-700">Phương thức thanh toán:</div>
                    <div>✓ Thẻ tín dụng / Ghi nợ</div>
                    <div>✓ Ví điện tử</div>
                    <div>✓ Chuyển khoản ngân hàng</div>
                    <div>✓ COD - Trả khi nhận hàng</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
