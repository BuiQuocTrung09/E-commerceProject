<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])){
    $pid=(int)($_POST['product_id']??0);
    $qty=max(1,(int)($_POST['qty']??1));
    if($pid>0){
        $chk=$pdo->prepare("SELECT id FROM products WHERE id=?");
        $chk->execute([$pid]);
        if($chk->fetch()) $_SESSION['cart'][$pid]=($_SESSION['cart'][$pid]??0)+$qty;
    }
    $qs=http_build_query($_GET);
    header('Location: shop.php'.($qs?('?'.$qs.'&added=1'):'?added=1'));
    exit;
}
$displayName=getDisplayName();
$cartCount=getCartCount();
$search=trim($_GET['search']??'');
$cat=trim($_GET['cat']??'');
$priceMin=isset($_GET['priceMin']) && $_GET['priceMin']!=='' ? (float)$_GET['priceMin'] : null;
$priceMax=isset($_GET['priceMax']) && $_GET['priceMax']!=='' ? (float)$_GET['priceMax'] : null;
$ratingFilter=(int)($_GET['rating']??0);
$sort=$_GET['sort']??'newest';
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
$countSql="SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereSql";
$stmt=$pdo->prepare($countSql); $stmt->execute($params); $total=(int)$stmt->fetchColumn();
$totalPages=max(1, ceil($total/$perPage));
if($page>$totalPages) $page=$totalPages;
$offset=($page-1)*$perPage;
$sql="SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereSql $orderSql LIMIT $perPage OFFSET $offset";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $products=$stmt->fetchAll(PDO::FETCH_ASSOC);
$categories=$pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
function buildUrl($overrides=[]){
    $q=$_GET;
    foreach($overrides as $k=>$v){ if($v===null) unset($q[$k]); else $q[$k]=$v; }
    return 'shop.php'.($q?('?'.http_build_query($q)):'');
}
$added=isset($_GET['added']);
$catName=''; if($cat!==''){ foreach($categories as $cc) if($cc['slug']===$cat){ $catName=$cc['name']; break; } if($catName==='') $catName=$cat; }
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Chợ — Chợ Gia Dụng</title>
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
.btn-terra:hover{background:var(--terracotta2);transform:translateY(-1px)}
.btn-ghost{border:1px solid var(--line);background:white;border-radius:999px}
.product-card{background:white;border:1px solid var(--line);border-radius:22px;overflow:hidden;transition:.25s}
.product-card:hover{transform:translateY(-3px);box-shadow:0 18px 40px rgba(28,25,22,.08)}
.img-wrap{background:var(--paper2)}
.badge-sale{position:absolute;top:12px;left:12px;background:var(--ink);color:white;font-size:11px;letter-spacing:.06em;padding:5px 9px;border-radius:999px;font-weight:600}
.price-new{font-family:'Fraunces',serif;font-weight:700;font-size:18px}
.price-old{font-size:12px;color:#9a8e81;text-decoration:line-through}
.star{color:#c9a14a;font-size:11px}
.sort-active{background:var(--ink);color:var(--paper);border-color:var(--ink)}
.filter-card{background:white;border:1px solid var(--line);border-radius:20px}
.mobile-menu{max-height:0;overflow:hidden;transition:max-height .28s ease}
.mobile-menu.active{max-height:400px}
.sidebar{transition:transform .28s ease}
@media(max-width:768px){.sidebar{position:fixed;left:0;top:0;width:300px;height:100vh;background:var(--paper);z-index:30;transform:translateX(-100%);overflow-y:auto;border-right:1px solid var(--line)} .sidebar.open{transform:translateX(0)} .overlay{position:fixed;inset:0;background:rgba(28,25,22,.35);backdrop-filter:blur(2px);display:none;z-index:25} .overlay.show{display:block}}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between"><span class="hidden md:inline">Tuyển chọn thủ công — Giao toàn quốc</span><span>Miễn phí vận chuyển đơn từ 500.000đ</span><a href="about.php" class="hidden md:inline hover:text-white/70">Câu chuyện xưởng →</a></div></div>

<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="flex items-center gap-6 py-4">
      <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold">cg</span><span class="leading-none"><span class="serif text-[20px] font-bold">Chợ Gia Dụng</span><span class="text-[11px] tracking-[.18em] uppercase text-[var(--muted)] block -mt-1">Est. 2014</span></span></a>
      <nav class="hidden lg:flex items-center gap-7 ml-8 text-sm">
        <a href="index.php" class="hover:text-black/60">Trang chủ</a>
        <a href="shop.php" class="font-semibold underline underline-offset-8">Chợ</a>
        <a href="about.php" class="hover:text-black/60">Câu chuyện</a>
      </nav>
      <div class="hidden md:flex items-center flex-1 max-w-[360px] ml-auto">
        <div class="pill flex items-center w-full px-1 h-[42px] gap-1">
          <i class="fa-solid fa-magnifying-glass text-[#9a8e81] ml-3 text-sm"></i>
          <input id="headerSearchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm nồi gang, bát gốm..." class="flex-1 bg-transparent outline-none text-[13px] px-2">
          <button id="headerSearchBtn" class="btn-terra px-5 h-[34px] text-[13px] font-semibold">Tìm</button>
        </div>
      </div>
      <div class="flex items-center gap-2 ml-auto md:ml-4">
        <div class="hidden md:flex items-center gap-2 pill h-[42px] px-4 text-sm"><i class="fa-regular fa-user"></i><span class="max-w-[110px] truncate"><?php echo $displayName; ?></span></div>
        <a href="cart.php" class="relative pill h-[42px] px-4 flex items-center gap-2 text-sm font-medium"><i class="fa-solid fa-bag-shopping"></i><span class="hidden sm:inline">Giỏ</span><span class="bg-[var(--terracotta)] text-white text-[11px] font-bold min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
        <button id="hamburger" class="lg:hidden pill w-[42px] h-[42px] grid place-items-center"><i class="fa-solid fa-bars"></i></button>
      </div>
    </div>
    <div id="mobileMenu" class="mobile-menu lg:hidden"><div class="py-3 space-y-2">
      <div class="flex gap-2"><div class="pill flex-1 flex items-center px-3 h-10 gap-2"><i class="fa-solid fa-magnifying-glass text-sm opacity-50"></i><input id="mSearch" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm..." class="flex-1 bg-transparent outline-none text-sm"></div><button onclick="const v=document.getElementById('mSearch').value.trim(); if(v) location.href='shop.php?search='+encodeURIComponent(v)" class="btn-terra px-5 h-10 text-sm font-semibold">Tìm</button></div>
      <a href="index.php" class="block px-3 py-3 bg-white border border-[var(--line)] rounded-xl text-sm">Trang chủ</a><a href="shop.php" class="block px-3 py-3 bg-[var(--ink)] text-white rounded-xl text-sm">Chợ</a>
    </div></div>
  </div>
</header>

<div class="max-w-[1280px] mx-auto px-4 py-4 text-[13px] text-[var(--muted)] flex items-center gap-2 flex-wrap">
  <a href="index.php" class="hover:text-[var(--ink)]">Trang chủ</a><span class="opacity-40">/</span><a href="shop.php" class="hover:text-[var(--ink)]">Chợ</a><?php if($catName): ?><span class="opacity-40">/</span><span class="text-[var(--ink)] font-medium"><?php echo htmlspecialchars($catName); ?></span><?php endif; ?><?php if($search): ?><span class="opacity-40">/</span><span>“<?php echo htmlspecialchars($search); ?>”</span><?php endif; ?>
</div>

<section class="max-w-[1280px] mx-auto px-4">
  <div class="bg-white border border-[var(--line)] rounded-[24px] p-6 md:p-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
    <div>
      <div class="text-[11px] tracking-[.16em] uppercase text-[var(--muted)]">Chợ — đồ dùng được lâu</div>
      <h1 class="serif text-[30px] md:text-[40px] leading-none mt-2"><?php echo $catName ? htmlspecialchars($catName) : 'Tất cả sản phẩm'; ?></h1>
      <p class="text-sm text-[var(--muted)] mt-3 max-w-[60ch]">Chọn ít nhưng chọn kỹ. Mỗi món đều có thể sửa, thay phụ kiện và dùng qua nhiều năm — càng dùng càng lên nước.</p>
      <?php if($search||$cat||$priceMin!==null||$priceMax!==null||$ratingFilter): ?><div class="flex flex-wrap gap-2 mt-4"><?php if($search): ?><span class="pill px-3 py-1 text-xs">Từ khóa: “<?php echo htmlspecialchars($search); ?>”</span><?php endif; ?><?php if($cat): ?><span class="pill px-3 py-1 text-xs"><?php echo htmlspecialchars($catName); ?></span><?php endif; ?><?php if($ratingFilter): ?><span class="pill px-3 py-1 text-xs">≥ <?php echo $ratingFilter; ?> sao</span><?php endif; ?><a href="shop.php" class="text-xs underline underline-offset-4">Xóa bộ lọc</a></div><?php endif; ?>
    </div>
    <div class="hidden md:block text-right"><div class="inline-flex items-center gap-2 pill px-4 py-2 text-sm"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> <?php echo $total; ?> món đang có sẵn</div><div class="text-xs text-[var(--muted)] mt-2">Gói giấy kraft · Giao 1–3 ngày</div></div>
  </div>
</section>

<?php if($added): ?><div class="max-w-[1280px] mx-auto px-4 mt-4"><div class="bg-[#f1eadf] border border-[var(--line)] rounded-2xl px-4 py-3 flex items-center justify-between text-sm"><span>Đã thêm vào giỏ — cảm ơn bạn.</span><a href="cart.php" class="font-semibold underline">Mở giỏ →</a></div></div><?php endif; ?>

<section class="max-w-[1280px] mx-auto px-4 mt-4 flex items-center gap-2 md:gap-3 sticky top-[74px] z-30 bg-[rgba(253,248,241,.92)] backdrop-blur rounded-2xl border border-[var(--line)] px-3 py-3 overflow-x-auto">
  <button id="filterToggle" class="md:hidden pill px-4 py-2 text-sm font-medium shrink-0"><i class="fa-solid fa-sliders mr-2"></i>Lọc</button>
  <div class="text-xs tracking-[.12em] uppercase text-[var(--muted)] hidden md:block shrink-0">Sắp xếp</div>
  <div class="flex items-center gap-2 shrink-0">
    <a href="<?php echo buildUrl(['sort'=>'newest','page'=>1]); ?>" class="pill px-4 py-2 text-[13px] <?php echo $sort==='newest'?'sort-active':''; ?>">Mới nhất</a>
    <a href="<?php echo buildUrl(['sort'=>'price-low','page'=>1]); ?>" class="pill px-4 py-2 text-[13px] <?php echo $sort==='price-low'?'sort-active':''; ?>">Giá thấp → cao</a>
    <a href="<?php echo buildUrl(['sort'=>'price-high','page'=>1]); ?>" class="pill px-4 py-2 text-[13px] <?php echo $sort==='price-high'?'sort-active':''; ?>">Giá cao → thấp</a>
    <a href="<?php echo buildUrl(['sort'=>'popular','page'=>1]); ?>" class="pill px-4 py-2 text-[13px] <?php echo $sort==='popular'?'sort-active':''; ?>">Yêu thích</a>
  </div>
  <form method="get" class="ml-auto hidden md:flex items-center gap-2 shrink-0">
    <?php if($cat) echo '<input type="hidden" name="cat" value="'.htmlspecialchars($cat).'">'; ?>
    <?php if($ratingFilter) echo '<input type="hidden" name="rating" value="'.$ratingFilter.'">'; ?>
    <?php if(isset($_GET['sort'])) echo '<input type="hidden" name="sort" value="'.htmlspecialchars($sort).'">'; ?>
    <?php if($priceMin!==null) echo '<input type="hidden" name="priceMin" value="'.$priceMin.'">'; ?>
    <?php if($priceMax!==null) echo '<input type="hidden" name="priceMax" value="'.$priceMax.'">'; ?>
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Lọc nhanh..." class="pill h-9 px-4 text-sm w-[200px] outline-none focus:border-[#d8cabd] bg-white">
    <button class="btn-terra px-4 h-9 text-sm">Lọc</button>
  </form>
</section>

<div class="max-w-[1280px] mx-auto px-4 py-6 flex gap-6 items-start">
  <div id="overlay" class="overlay"></div>
  <aside id="sidebar" class="sidebar w-[300px] shrink-0">
    <div class="p-4 md:p-0 space-y-4">
      <div class="flex items-center justify-between md:hidden px-1 py-2"><div class="font-semibold">Bộ lọc</div><button id="closeSidebar" class="pill w-8 h-8 grid place-items-center"><i class="fa-solid fa-xmark text-xs"></i></button></div>

      <form method="get" class="space-y-4">
        <?php if($search) echo '<input type="hidden" name="search" value="'.htmlspecialchars($search).'">'; ?>
        <?php if($sort) echo '<input type="hidden" name="sort" value="'.htmlspecialchars($sort).'">'; ?>

        <div class="filter-card p-5">
          <div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)]">Danh mục</div>
          <div class="mt-3 space-y-2">
            <label class="flex items-center gap-3 text-sm cursor-pointer"><input type="radio" name="cat" value="" <?php echo $cat===''?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> <span>Tất cả</span> <span class="ml-auto text-xs text-[var(--muted)]"><?php echo $total; ?></span></label>
            <?php foreach($categories as $c): ?>
            <label class="flex items-center gap-3 text-sm cursor-pointer"><input type="radio" name="cat" value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo $cat===$c['slug']?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> <span><?php echo htmlspecialchars($c['name']); ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-card p-5">
          <div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)]">Khoảng giá</div>
          <div class="mt-3 flex items-center gap-2">
            <input type="number" name="priceMin" value="<?php echo $priceMin!==null?htmlspecialchars((string)$priceMin):''; ?>" placeholder="Từ" class="w-full pill h-10 px-3 text-sm outline-none bg-white">
            <span class="text-[var(--muted)]">—</span>
            <input type="number" name="priceMax" value="<?php echo $priceMax!==null?htmlspecialchars((string)$priceMax):''; ?>" placeholder="Đến" class="w-full pill h-10 px-3 text-sm outline-none bg-white">
          </div>
          <button type="submit" class="btn-terra w-full mt-3 py-2.5 text-sm font-semibold">Áp dụng giá</button>
          <div class="text-xs text-[var(--muted)] mt-2 text-center">Gợi ý: 100k — 500k — 1tr+</div>
        </div>

        <div class="filter-card p-5">
          <div class="text-[11px] tracking-[.14em] uppercase text-[var(--muted)]">Đánh giá</div>
          <div class="mt-3 space-y-2">
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="rating" value="0" <?php echo $ratingFilter==0?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> Tất cả</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="rating" value="5" <?php echo $ratingFilter==5?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> <span class="flex gap-0.5 text-[#c9a14a] text-xs"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span> 5 sao</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="rating" value="4" <?php echo $ratingFilter==4?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> 4 sao trở lên</label>
            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="rating" value="3" <?php echo $ratingFilter==3?'checked':''; ?> onchange="this.form.submit()" class="accent-[#1c1916]"> 3 sao trở lên</label>
          </div>
        </div>

        <a href="shop.php" class="btn-ghost w-full py-3 text-sm font-medium flex items-center justify-center gap-2"><i class="fa-solid fa-rotate-left text-xs"></i> Đặt lại bộ lọc</a>
        <div class="bg-[#1c1916] text-[#fdf8f1] rounded-2xl p-5">
          <div class="text-sm font-semibold">Cần tư vấn?</div><div class="text-xs text-white/60 mt-1">Nhắn tụi mình, trả lời trong ngày.</div><a href="about.php" class="mt-3 inline-flex pill bg-white text-[#1c1916] px-4 py-2 text-xs font-semibold">Liên hệ xưởng</a>
        </div>
      </form>
    </div>
  </aside>

  <div class="flex-1 min-w-0">
    <div class="flex items-center justify-between text-sm mb-4">
      <div class="text-[var(--muted)]">Hiển thị <span class="font-semibold text-[var(--ink)]"><?php echo count($products); ?></span> / <?php echo $total; ?> sản phẩm <?php if($totalPages>1) echo "· trang $page/$totalPages"; ?></div>
      <div class="hidden md:flex items-center gap-2 text-xs text-[var(--muted)]"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> Còn hàng & giao nhanh</div>
    </div>

    <?php if(empty($products)): ?>
      <div class="bg-white border border-[var(--line)] rounded-[22px] p-10 text-center">
        <div class="w-14 h-14 mx-auto rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-magnifying-glass"></i></div>
        <div class="font-semibold mt-4">Không tìm thấy món phù hợp</div><div class="text-sm text-[var(--muted)] mt-1">Thử nới khoảng giá hoặc bỏ bớt bộ lọc nhé.</div>
        <a href="shop.php" class="btn-terra inline-flex px-6 py-3 text-sm font-semibold mt-4">Xem tất cả</a>
      </div>
    <?php else: ?>
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <?php foreach($products as $p): $discount=0; if(!empty($p['old_price']) && $p['old_price']>$p['price']) $discount=round((1-$p['price']/$p['old_price'])*100); ?>
      <div class="product-card flex flex-col">
        <a href="product-details.php?id=<?php echo $p['id']; ?>" class="img-wrap h-[170px] grid place-items-center relative block overflow-hidden">
          <?php if($discount>0): ?><span class="badge-sale">-<?php echo $discount; ?>%</span><?php endif; ?>
          <?php if(!empty($p['image'])): ?><img src="<?php echo htmlspecialchars($p['image']); ?>" class="w-full h-full object-cover"><?php else: ?><span class="text-3xl">🍶</span><?php endif; ?>
          <span class="absolute bottom-3 left-3 bg-white/90 backdrop-blur border border-[var(--line)] text-[11px] px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($p['cat_name']??'Thủ công'); ?></span>
        </a>
        <div class="p-4 flex flex-col flex-1">
          <a href="product-details.php?id=<?php echo $p['id']; ?>" class="font-medium leading-tight line-clamp-2 hover:text-[var(--terracotta)]"><?php echo htmlspecialchars($p['name']); ?></a>
          <div class="flex items-center gap-1 mt-2"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star star <?php echo $i<=round($p['rating'])?'':'opacity-20'; ?>"></i><?php endfor; ?><span class="text-xs text-[var(--muted)] ml-1"><?php echo (int)$p['review_count']; ?></span></div>
          <div class="mt-3"><span class="price-new text-[17px]"><?php echo formatPrice($p['price']); ?></span><?php if($discount>0): ?><span class="price-old ml-1"><?php echo formatPrice($p['old_price']); ?></span><?php endif; ?></div>
          <form method="post" class="mt-3"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><button name="add_to_cart" class="w-full btn-terra py-2.5 text-sm font-semibold">Thêm vào giỏ</button></form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if($totalPages>1): ?>
    <div class="flex items-center justify-center gap-1.5 mt-8 flex-wrap">
      <?php if($page>1): ?><a href="<?php echo buildUrl(['page'=>$page-1]); ?>" class="pill w-9 h-9 grid place-items-center hover:border-[#d8cabd]"><i class="fa-solid fa-chevron-left text-xs"></i></a><?php endif; ?>
      <?php for($i=1;$i<=$totalPages;$i++): if($i>3 && $i<$totalPages-1 && abs($i-$page)>1){ if($i==4 && $page<$totalPages-2) echo '<span class="px-2 text-sm text-[var(--muted)]">…</span>'; continue; } ?>
        <a href="<?php echo buildUrl(['page'=>$i]); ?>" class="min-w-[36px] h-9 grid place-items-center rounded-full text-sm border <?php echo $i==$page?'bg-[var(--ink)] text-white border-[var(--ink)]':'bg-white border-[var(--line)] hover:border-[#d8cabd]'; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <?php if($page<$totalPages): ?><a href="<?php echo buildUrl(['page'=>$page+1]); ?>" class="pill w-9 h-9 grid place-items-center hover:border-[#d8cabd]"><i class="fa-solid fa-chevron-right text-xs"></i></a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const hamburger=document.getElementById('hamburger');
const mobileMenu=document.getElementById('mobileMenu');
if(hamburger) hamburger.addEventListener('click',()=>mobileMenu.classList.toggle('active'));
const filterToggle=document.getElementById('filterToggle');
const sidebar=document.getElementById('sidebar');
const closeSidebar=document.getElementById('closeSidebar');
const overlay=document.getElementById('overlay');
function openSide(){ sidebar.classList.add('open'); overlay.classList.add('show'); }
function closeSide(){ sidebar.classList.remove('open'); overlay.classList.remove('show'); }
if(filterToggle) filterToggle.addEventListener('click',openSide);
if(closeSidebar) closeSidebar.addEventListener('click',closeSide);
if(overlay) overlay.addEventListener('click',closeSide);
const hInput=document.getElementById('headerSearchInput');
const hBtn=document.getElementById('headerSearchBtn');
function doHSearch(){ const v=(hInput.value||'').trim(); if(v){ const url=new URL(location.href); url.searchParams.set('search',v); url.searchParams.set('page','1'); location.href=url.toString(); } }
if(hBtn) hBtn.addEventListener('click',doHSearch);
if(hInput) hInput.addEventListener('keypress',e=>{ if(e.key==='Enter'){ e.preventDefault(); doHSearch(); }});
setTimeout(()=>{ const t=document.querySelector('.bg-\\[\\#f1eadf\\]'); if(t) t.style.display='none'; },2600);
</script>
</body>
</html>
