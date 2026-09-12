<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

// Handle add to cart
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    $pid=(int)($_POST['product_id']??0);
    $qty=max(1,(int)($_POST['qty']??1));
    if($pid>0){
        $chk=$pdo->prepare("SELECT id FROM products WHERE id=?");
        $chk->execute([$pid]);
        if($chk->fetch()) $_SESSION['cart'][$pid]=($_SESSION['cart'][$pid]??0)+$qty;
    }
    // redirect back to shop with params
    $qs=http_build_query($_GET);
    header('Location: shop.php'.($qs?('?'.$qs.'&added=1'):'?added=1'));
    exit;
}

$displayName=getDisplayName();
$cartCount=getCartCount();

// Filters
$search=trim($_GET['search']??'');
$cat=trim($_GET['cat']??'');
$priceMin=isset($_GET['priceMin']) && $_GET['priceMin']!=='' ? (float)$_GET['priceMin'] : null;
$priceMax=isset($_GET['priceMax']) && $_GET['priceMax']!=='' ? (float)$_GET['priceMax'] : null;
$ratingFilter = (int)($_GET['rating']??0);
$sort= $_GET['sort']??'newest';
$allowedSort=['newest','price-low','price-high','popular'];
if(!in_array($sort,$allowedSort)) $sort='newest';
$page=max(1,(int)($_GET['page']??1));
$perPage=8;

$where=[]; $params=[];
if($search!==''){ $where[]="(p.name LIKE :search OR p.description LIKE :search)"; $params['search']="%$search%"; }
if($cat!=='' && $cat!=='all'){ $where[]="c.slug=:cat"; $params['cat']=$cat; }
if($priceMin!==null){ $where[]="p.price >= :pmin"; $params['pmin']=$priceMin; }
if($priceMax!==null){ $where[]="p.price <= :pmax"; $params['pmax']=$priceMax; }
if($ratingFilter>0){ $where[]="p.rating >= :rating"; $params['rating']=$ratingFilter; }

$whereSql=$where ? 'WHERE '.implode(' AND ',$where) : '';
$orderSql=match($sort){
    'price-low'=>'ORDER BY p.price ASC',
    'price-high'=>'ORDER BY p.price DESC',
    'popular'=>'ORDER BY p.review_count DESC, p.rating DESC',
    default=>'ORDER BY p.id DESC'
};

// count
$countSql="SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereSql";
$stmt=$pdo->prepare($countSql);
$stmt->execute($params);
$total=(int)$stmt->fetchColumn();
$totalPages=max(1, ceil($total/$perPage));
if($page>$totalPages) $page=$totalPages;
$offset=($page-1)*$perPage;

$sql="SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereSql $orderSql LIMIT $perPage OFFSET $offset";
$stmt=$pdo->prepare($sql);
$stmt->execute($params);
$products=$stmt->fetchAll(PDO::FETCH_ASSOC);

// categories for sidebar
$categories=$pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

function buildUrl($overrides=[]){
    $q=$_GET;
    foreach($overrides as $k=>$v){
        if($v===null) unset($q[$k]); else $q[$k]=$v;
    }
    return 'shop.php'.($q?('?'.http_build_query($q)):'');
}
$added=isset($_GET['added']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chợ | Chợ gia dụng lớn nhất VN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        *{font-family:'Montserrat',sans-serif;}
        .nav-link:hover{color:#ff6b6b;transition:color 0.3s ease;}
        .product-card{transition:transform 0.3s ease,box-shadow 0.3s ease;}
        .product-card:hover{transform:translateY(-8px);box-shadow:0 10px 25px rgba(0,0,0,0.15);}
        header{transition:all 0.3s ease;}
        header.scrolled{padding:8px 0 !important;}
        header.scrolled .logo{width:100px !important;}
        .hamburger-menu{display:none;}
        .mobile-menu{position:fixed;top:60px;left:0;width:100%;background:#1a1a1a;max-height:0;overflow:hidden;transition:max-height 0.3s ease;z-index:40;}
        .mobile-menu.active{max-height:400px;}
        .mobile-menu a{display:block;padding:12px 16px;color:white;text-decoration:none;border-bottom:1px solid #333;transition:background 0.2s ease;}
        .mobile-menu a:hover{background:#333;color:#ff6b6b;}
        .sidebar{transition:transform 0.3s ease;}
        .sidebar.mobile-open{transform:translateX(0);}
        @media (max-width:768px){
            .sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:white;z-index:30;transform:translateX(-100%);overflow-y:auto;}
            .sidebar-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;z-index:25;}
            .sidebar-overlay.active{display:block;}
        }
        .filter-section{border-bottom:1px solid #e5e7eb;padding:16px 0;}
        .filter-section:last-child{border-bottom:none;}
        .filter-title{font-weight:600;margin-bottom:12px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;}
        .filter-title i{transition:transform 0.3s ease;}
        .filter-title.collapsed i{transform:rotate(-90deg);}
        .filter-content{max-height:500px;overflow:hidden;transition:max-height 0.3s ease;}
        .filter-content.collapsed{max-height:0;}
        .filter-option{display:flex;align-items:center;gap:8px;margin-bottom:10px;cursor:pointer;}
        .filter-option input[type="checkbox"],.filter-option input[type="radio"]{cursor:pointer;}
        .filter-option label{cursor:pointer;flex:1;}
        .price-range-container{display:flex;gap:8px;align-items:center;}
        .price-input{width:80px;padding:6px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;}
        .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
        @media (max-width:768px){.products-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));}}
        .sort-option{padding:8px 16px;border:1px solid #d1d5db;border-radius:4px;background:white;cursor:pointer;transition:all 0.2s ease;font-size:13px;}
        .sort-option:hover{border-color:#ff6b6b;color:#ff6b6b;}
        .sort-option.active{background:#ff6b6b;color:white;border-color:#ff6b6b;}
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-[#1a1a1a] text-white sticky top-0 z-50 shadow-lg py-4">
        <nav class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-8 flex-1">
                <a href="index.php"><img src="../Pics/logo_nenden.png" alt="Logo" class="logo w-40 cursor-pointer hover:opacity-80 transition" /></a>
                <div class="nav-menu hidden md:flex gap-6 items-center">
                    <a href="index.php" class="nav-link text-white text-sm font-medium">Trang chủ</a>
                    <a href="shop.php" class="nav-link text-white text-sm font-medium text-red-400">Chợ</a>
                    <a href="about.php" class="nav-link text-white text-sm font-medium">Về chúng tôi</a>
                    <a href="#contact" class="nav-link text-white text-sm font-medium">Liên hệ</a>
                </div>
            </div>
            <div class="search-bar hidden md:flex flex-1 max-w-xs mx-6">
                <div class="w-full relative">
                    <input type="text" placeholder="Tìm kiếm sản phẩm..." id="headerSearchInput" value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full px-4 py-2 rounded-lg text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                    <button class="absolute right-2 top-2 text-gray-600" id="headerSearchBtn"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="right-icons flex items-center gap-6">
                <div class="hidden md:flex items-center gap-2 relative group">
                    <div class="flex items-center gap-2 cursor-pointer hover:text-red-500"><i class="fas fa-user text-xl"></i><span class="text-sm font-medium"><?php echo $displayName; ?></span><i class="fas fa-chevron-down text-xs"></i></div>
                    <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow-lg w-48 py-2 z-50">
                        <?php if(isLoggedIn()): ?>
                            <div class="px-4 py-2 text-sm border-b"><div class="font-semibold truncate"><?php echo $displayName; ?></div><div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION['email']); ?></div></div>
                            <a href="cart.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Giỏ hàng (<?php echo $cartCount; ?>)</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm hover:bg-gray-50 text-red-500">Đăng xuất</a>
                        <?php else: ?>
                            <a href="login.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng nhập</a>
                            <a href="register.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="flex items-center gap-2 cursor-pointer hover:text-red-500 relative"><i class="fas fa-shopping-cart text-xl"></i><span class="text-sm font-medium hidden sm:inline">Giỏ hàng</span><span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span></a>
                <button class="hamburger-menu text-xl focus:outline-none"><i class="fas fa-bars"></i></button>
            </div>
        </nav>
    </header>
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php">Trang chủ</a>
        <a href="shop.php">Chợ</a>
        <a href="about.php">Về chúng tôi</a>
        <a href="term.php">Điều khoản</a>
        <?php if(isLoggedIn()): ?><a href="logout.php" class="text-red-400">Đăng xuất (<?php echo $displayName; ?>)</a><?php else: ?><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a><?php endif; ?>
    </div>

    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-3 text-sm text-gray-600"><a href="index.php" class="hover:text-red-500">Trang chủ</a><span class="mx-2">/</span><span>Chợ</span><?php if($cat): ?><span class="mx-2">/</span><span class="text-red-500"><?php echo htmlspecialchars($categories[array_search($cat,array_column($categories,'slug'))]['name'] ?? $cat); ?></span><?php endif; ?></div>
    </div>

    <div class="bg-white border-b border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-3xl font-bold text-gray-800">Tất Cả Sản Phẩm</h1>
            <p class="text-gray-600 mt-2">Khám phá bộ sưu tập đầy đủ các sản phẩm gia dụng của chúng tôi <?php if($search) echo '- kết quả cho: <strong class="text-red-500">'.htmlspecialchars($search).'</strong>'; ?></p>
        </div>
    </div>
    <?php if($added): ?><div class="bg-green-50 border-b border-green-200 text-green-700 px-4 py-3 text-center text-sm">Đã thêm vào giỏ hàng! <a href="cart.php" class="font-bold underline">Xem giỏ hàng</a></div><?php endif; ?>

    <div class="bg-white border-b border-gray-200 sticky top-[68px] z-40">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <button class="md:hidden flex items-center gap-2 text-gray-700 hover:text-red-500 self-start" id="filterToggle"><i class="fas fa-sliders-h"></i><span>Lọc</span></button>
                <form method="get" class="w-full md:flex-1 md:max-w-sm relative flex gap-2" id="searchForm">
                    <?php if($cat) echo '<input type="hidden" name="cat" value="'.htmlspecialchars($cat).'">'; ?>
                    <?php if($ratingFilter) echo '<input type="hidden" name="rating" value="'.$ratingFilter.'">'; ?>
                    <?php if(isset($_GET['sort'])) echo '<input type="hidden" name="sort" value="'.htmlspecialchars($sort).'">'; ?>
                    <?php if($priceMin!==null) echo '<input type="hidden" name="priceMin" value="'.$priceMin.'">'; ?>
                    <?php if($priceMax!==null) echo '<input type="hidden" name="priceMax" value="'.$priceMax.'">'; ?>
                    <div class="flex-1 relative">
                        <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                        <button type="submit" class="absolute right-3 top-2 text-gray-600"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <div class="flex gap-2 flex-wrap justify-end items-center">
                    <span class="text-sm text-gray-700 font-medium hidden md:block">Sắp xếp:</span>
                    <a href="<?php echo buildUrl(['sort'=>'newest','page'=>1]); ?>" class="sort-option <?php echo $sort==='newest'?'active':''; ?>">Mới nhất</a>
                    <a href="<?php echo buildUrl(['sort'=>'price-low','page'=>1]); ?>" class="sort-option <?php echo $sort==='price-low'?'active':''; ?>">Giá: Thấp → Cao</a>
                    <a href="<?php echo buildUrl(['sort'=>'price-high','page'=>1]); ?>" class="sort-option <?php echo $sort==='price-high'?'active':''; ?>">Giá: Cao → Thấp</a>
                    <a href="<?php echo buildUrl(['sort'=>'popular','page'=>1]); ?>" class="sort-option <?php echo $sort==='popular'?'active':''; ?>">Phổ biến</a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 flex gap-6">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar w-80 flex-shrink-0 bg-white rounded-lg shadow-sm p-6 h-fit">
            <div class="flex items-center justify-between md:hidden mb-4">
                <h2 class="text-lg font-bold text-gray-800">Lọc Sản Phẩm</h2>
                <button id="closeSidebar" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form method="get" id="filterForm">
                <?php if($search) echo '<input type="hidden" name="search" value="'.htmlspecialchars($search).'">'; ?>
                <?php if($sort) echo '<input type="hidden" name="sort" value="'.htmlspecialchars($sort).'">'; ?>
            <div class="filter-section">
                <div class="filter-title"><span class="text-gray-800">Danh Mục</span><i class="fas fa-chevron-down"></i></div>
                <div class="filter-content">
                    <label class="filter-option"><input type="radio" name="cat" value="" <?php echo $cat===''?'checked':''; ?> onchange="this.form.submit()"> <span>Tất cả</span></label>
                    <?php foreach($categories as $c): ?>
                    <label class="filter-option"><input type="radio" name="cat" value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo $cat===$c['slug']?'checked':''; ?> onchange="this.form.submit()"> <span><?php echo htmlspecialchars($c['name']); ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-section">
                <div class="filter-title"><span class="text-gray-800">Giá</span><i class="fas fa-chevron-down"></i></div>
                <div class="filter-content">
                    <div class="mb-4">
                        <div class="price-range-container">
                            <input type="number" name="priceMin" value="<?php echo $priceMin!==null?htmlspecialchars($priceMin):''; ?>" class="price-input" placeholder="Từ" min="0" />
                            <span class="text-gray-600">-</span>
                            <input type="number" name="priceMax" value="<?php echo $priceMax!==null?htmlspecialchars($priceMax):''; ?>" class="price-input" placeholder="Đến" min="0" />
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition text-sm font-medium">Áp dụng</button>
                </div>
            </div>
            <div class="filter-section">
                <div class="filter-title"><span class="text-gray-800">Đánh Giá</span><i class="fas fa-chevron-down"></i></div>
                <div class="filter-content">
                    <label class="filter-option"><input type="radio" name="rating" value="0" <?php echo $ratingFilter==0?'checked':''; ?> onchange="this.form.submit()"> <span>Tất cả</span></label>
                    <label class="filter-option"><input type="radio" name="rating" value="5" <?php echo $ratingFilter==5?'checked':''; ?> onchange="this.form.submit()"> <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400 text-xs"></i><i class="fas fa-star text-yellow-400 text-xs"></i><i class="fas fa-star text-yellow-400 text-xs"></i><i class="fas fa-star text-yellow-400 text-xs"></i><i class="fas fa-star text-yellow-400 text-xs"></i> 5 sao</span></label>
                    <label class="filter-option"><input type="radio" name="rating" value="4" <?php echo $ratingFilter==4?'checked':''; ?> onchange="this.form.submit()"> <span>4 sao trở lên</span></label>
                    <label class="filter-option"><input type="radio" name="rating" value="3" <?php echo $ratingFilter==3?'checked':''; ?> onchange="this.form.submit()"> <span>3 sao trở lên</span></label>
                </div>
            </div>
            <a href="shop.php" class="w-full mt-6 border border-gray-300 text-gray-700 hover:bg-gray-50 py-2 rounded-lg transition text-sm font-medium flex items-center justify-center"><i class="fas fa-redo mr-2"></i>Đặt lại bộ lọc</a>
            </form>
        </aside>

        <div class="flex-1">
            <div class="mb-6 text-sm text-gray-600 flex flex-wrap gap-2 items-center justify-between">
                <div>Hiển thị <span class="font-bold text-gray-800"><?php echo count($products); ?></span> trong <?php echo $total; ?> sản phẩm <?php if($totalPages>1) echo "— trang $page / $totalPages"; ?></div>
                <?php if($search||$cat||$priceMin!==null||$priceMax!==null||$ratingFilter): ?><a href="shop.php" class="text-red-500 text-sm hover:underline">Xóa bộ lọc</a><?php endif; ?>
            </div>

            <?php if(empty($products)): ?>
                <div class="bg-white rounded-lg p-12 text-center">
                    <div class="text-5xl mb-4 text-gray-300"><i class="fas fa-search"></i></div>
                    <h3 class="font-bold text-gray-700 mb-2">Không tìm thấy sản phẩm</h3>
                    <p class="text-gray-500 text-sm mb-4">Thử thay đổi từ khóa hoặc bộ lọc.</p>
                    <a href="shop.php" class="inline-block bg-red-500 text-white px-6 py-2 rounded-lg">Xem tất cả</a>
                </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach($products as $p):
                    $discount=0;
                    if(!empty($p['old_price']) && $p['old_price']>$p['price']) $discount=round((1-$p['price']/$p['old_price'])*100);
                ?>
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-md flex flex-col">
                    <a href="product-details.php?id=<?php echo $p['id']; ?>" class="relative block">
                        <div class="bg-gray-200 h-40 flex items-center justify-center overflow-hidden">
                            <?php if(!empty($p['image'])): ?><img src="<?php echo htmlspecialchars($p['image']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['name']); ?>"><?php else: ?><i class="fas fa-image text-3xl text-gray-400"></i><?php endif; ?>
                        </div>
                        <?php if($discount>0): ?><span class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">-<?php echo $discount; ?>%</span><?php endif; ?>
                    </a>
                    <div class="p-4 flex-1 flex flex-col">
                        <a href="product-details.php?id=<?php echo $p['id']; ?>" class="font-bold text-gray-800 mb-1 text-sm line-clamp-2 hover:text-red-500"><?php echo htmlspecialchars($p['name']); ?></a>
                        <div class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($p['cat_name']??''); ?></div>
                        <div class="flex items-center gap-1 mb-2">
                            <?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star text-xs <?php echo $i<=round($p['rating'])?'text-yellow-400':'text-gray-300'; ?>"></i><?php endfor; ?>
                            <span class="text-xs text-gray-600">(<?php echo (int)$p['review_count']; ?>)</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-lg font-bold text-red-500"><?php echo formatPrice($p['price']); ?></span>
                            <?php if($discount>0): ?><span class="text-xs text-gray-500 line-through ml-1"><?php echo formatPrice($p['old_price']); ?></span><?php endif; ?>
                        </div>
                        <form method="post" class="mt-auto"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><button type="submit" name="add_to_cart" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition text-sm font-medium"><i class="fas fa-cart-plus mr-2"></i>Thêm vào giỏ</button></form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if($totalPages>1): ?>
            <div class="flex items-center justify-center gap-2 mt-12 flex-wrap">
                <?php if($page>1): ?><a href="<?php echo buildUrl(['page'=>$page-1]); ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                <?php for($i=1;$i<=$totalPages;$i++): if($i>3 && $i<$totalPages-1 && abs($i-$page)>1){ if($i==4 && $page< $totalPages-2) echo '<span class="px-2">...</span>'; continue; } ?>
                    <a href="<?php echo buildUrl(['page'=>$i]); ?>" class="px-4 py-2 rounded-lg <?php echo $i==$page?'bg-red-500 text-white':'border border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if($page<$totalPages): ?><a href="<?php echo buildUrl(['page'=>$page+1]); ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const header=document.querySelector('header');
        const hamburger=document.querySelector('.hamburger-menu');
        const mobileMenu=document.getElementById('mobileMenu');
        window.addEventListener('scroll',()=>{ if(window.scrollY>100) header.classList.add('scrolled'); else header.classList.remove('scrolled'); });
        if(hamburger) hamburger.addEventListener('click',()=>mobileMenu.classList.toggle('active'));
        const filterToggle=document.getElementById('filterToggle');
        const sidebar=document.querySelector('.sidebar');
        const closeSidebar=document.getElementById('closeSidebar');
        const sidebarOverlay=document.getElementById('sidebarOverlay');
        if(filterToggle) filterToggle.addEventListener('click',()=>{ sidebar.classList.add('mobile-open'); sidebarOverlay.classList.add('active'); });
        if(closeSidebar) closeSidebar.addEventListener('click',()=>{ sidebar.classList.remove('mobile-open'); sidebarOverlay.classList.remove('active'); });
        if(sidebarOverlay) sidebarOverlay.addEventListener('click',()=>{ sidebar.classList.remove('mobile-open'); sidebarOverlay.classList.remove('active'); });
        document.querySelectorAll('.filter-title').forEach(t=>t.addEventListener('click',()=>{ t.classList.toggle('collapsed'); t.nextElementSibling.classList.toggle('collapsed'); }));
        const hInput=document.getElementById('headerSearchInput');
        const hBtn=document.getElementById('headerSearchBtn');
        function doHSearch(){ const v=(hInput.value||'').trim(); if(v){ const url=new URL(window.location.href); url.searchParams.set('search',v); url.searchParams.set('page','1'); location.href=url.toString(); } }
        if(hBtn) hBtn.addEventListener('click',doHSearch);
        if(hInput) hInput.addEventListener('keypress',e=>{ if(e.key==='Enter'){ e.preventDefault(); doHSearch(); } });
        setTimeout(()=>{ const t=document.querySelector('.bg-green-50'); if(t) t.style.display='none'; },2500);
    </script>
</body>
</html>
