<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
requireAdmin();

$msg=''; $err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrfCheck();
    $action=$_POST['action']??'';

    /* ---- Sửa thông tin user ---- */
    if($action==='edit_user'){
        $uid=(int)($_POST['user_id']??0);
        $firstName=trim($_POST['first_name']??'');
        $lastName=trim($_POST['last_name']??'');
        $phone=trim($_POST['phone']??'');
        $address=trim($_POST['address']??'');
        $city=trim($_POST['city']??'');
        if($uid>0 && $firstName!=='' && $lastName!==''){
            $st=$pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,address=?,city=? WHERE id=?");
            $st->execute([$firstName,$lastName,$phone,$address,$city,$uid]);
            $msg='Đã cập nhật thông tin người dùng #'.$uid.'.';
        } else { $err='Thông tin chưa hợp lệ.'; }
    }

    /* ---- Đổi quyền ---- */
    if($action==='set_role'){
        $uid=(int)($_POST['user_id']??0);
        $role=($_POST['role']??'')==='admin'?'admin':'user';
        if($uid===$_SESSION['user_id']){ $err='Không thể tự hạ quyền của chính mình.'; }
        elseif($uid>0){
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
            $msg='Đã chuyển quyền người dùng #'.$uid.' thành '.$role.'.';
        }
    }

    /* ---- Xóa user ---- */
    if($action==='delete_user'){
        $uid=(int)($_POST['user_id']??0);
        if($uid===$_SESSION['user_id']){ $err='Không thể xóa tài khoản của chính mình.'; }
        elseif($uid>0){
            try{
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
                $msg='Đã xóa người dùng #'.$uid.'.';
            }catch(PDOException $e){ $err='Không xóa được: người này có đơn hàng trong hệ thống.'; }
        }
    }

    /* ---- Admin đặt lại mật khẩu (không cần OTP — đã đăng nhập quản trị) ---- */
    if($action==='reset_password'){
        $uid=(int)($_POST['user_id']??0);
        $new=$_POST['new_password']??'';
        if($uid>0 && strlen($new)>=8 && preg_match('/[a-z]/',$new) && preg_match('/[A-Z]/',$new) && preg_match('/[0-9]/',$new)){
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$uid]);
            // Vô hiệu mọi OTP đang chờ của user này
            $pdo->prepare("UPDATE password_resets SET used=1 WHERE user_id=? AND used=0")->execute([$uid]);
            $msg='Đã đặt lại mật khẩu người dùng #'.$uid.'.';
        } else { $err='Mật khẩu mới cần 8+ ký tự, có chữ hoa, thường và số.'; }
    }

    /* ---- Cập nhật trạng thái đơn ---- */
    if($action==='order_status'){
        $oid=(int)($_POST['order_id']??0);
        $status=$_POST['status']??'Pending';
        $allowed=['Pending','Processing','Shipping','Completed','Cancelled'];
        if($oid>0 && in_array($status,$allowed)){
            $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$oid]);
            $msg='Đã cập nhật đơn #'.$oid.' → '.$status.'.';
        }
    }
}

/* ---------- Thống kê ---------- */
$stats=[
    'users'   => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'products'=> (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders'  => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status!='Cancelled'")->fetchColumn(),
];

/* ---------- Danh sách user (kèm số đơn) ---------- */
$q=trim($_GET['q']??'');
if($q!==''){
    $like="%$q%";
    $users=$pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count
                          FROM users u WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ?
                          ORDER BY u.id ASC");
    $users->execute([$like,$like,$like,$like]);
    $users=$users->fetchAll(PDO::FETCH_ASSOC);
} else {
    $users=$pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count FROM users u ORDER BY u.id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- Đơn hàng gần đây ---------- */
$orders=$pdo->query("SELECT o.*, u.first_name, u.last_name, u.email,
    (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.order_id=o.id) AS item_count
    FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

$statusColor=['Pending'=>'#e8b84a','Processing'=>'#5b8def','Shipping'=>'#c45b2f','Completed'=>'#2e7d32','Cancelled'=>'#9a8e81'];
$displayName=getDisplayName();
$cartCount=getCartCount();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Quản trị — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
.btn-terra:hover{background:var(--terracotta2)}
.btn-ghost{border:1px solid var(--line);background:white;border-radius:999px}
.card{background:white;border:1px solid var(--line);border-radius:22px}
.input{border:1px solid var(--line);background:var(--cream);border-radius:12px;height:40px;padding:0 12px;outline:none;width:100%;font-size:14px}
.input:focus{border-color:#d8cabd;background:white}
select.input{height:40px}
table{border-collapse:separate;border-spacing:0}
th{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:600;text-align:left;padding:10px 14px;border-bottom:1px solid var(--line)}
td{padding:12px 14px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tbody tr:hover{background:#fbf5ec}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600}
dialog{border:none;border-radius:20px;padding:0;max-width:480px;width:92vw}
dialog::backdrop{background:rgba(28,25,22,.4);backdrop-filter:blur(3px)}
.label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between"><span class="hidden md:inline">Bảng điều khiển quản trị</span><span>Chợ Gia Dụng · Admin</span><a href="shop.php" class="hidden md:inline hover:text-white/70">Xem cửa hàng →</a></div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="admin.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[var(--terracotta)] text-white grid place-items-center text-[13px]"><i class="fa-solid fa-shield-halved"></i></span><span class="leading-none"><span class="serif text-[20px] font-bold">Quản trị</span><span class="text-[11px] tracking-[.18em] uppercase text-[var(--muted)] block -mt-1">Chợ Gia Dụng</span></span></a>
    <nav class="hidden md:flex items-center gap-6 text-sm ml-6"><a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="profile.php" class="hover:text-black/60">Tài khoản</a></nav>
    <div class="ml-auto flex items-center gap-2">
      <span class="hidden md:inline-flex items-center gap-2 pill h-[42px] px-4 text-sm bg-white"><i class="fa-regular fa-user"></i><span class="max-w-[110px] truncate"><?php echo $displayName; ?></span></span>
      <a href="logout.php" class="pill h-[42px] px-4 flex items-center text-sm text-[var(--terracotta)] font-medium hover:bg-white">Đăng xuất</a>
    </div>
  </div>
</header>

<div class="max-w-[1280px] mx-auto px-4 py-6">
  <h1 class="serif text-[30px] md:text-[36px] leading-none">Bảng điều khiển</h1>
  <p class="text-sm text-[var(--muted)] mt-2">Người dùng, đơn hàng và hoạt động của cửa hàng — gói gọn trong một trang.</p>

  <?php if($msg): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
  <?php if($err): ?><div class="mt-5 bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

  <!-- Thống kê -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    <div class="card p-5"><div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Người dùng</div><div class="serif text-[30px] font-bold mt-1"><?php echo $stats['users']; ?></div><div class="text-xs text-[var(--muted)] mt-1"><i class="fa-solid fa-users mr-1"></i>tài khoản đã tạo</div></div>
    <div class="card p-5"><div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Đơn hàng</div><div class="serif text-[30px] font-bold mt-1"><?php echo $stats['orders']; ?></div><div class="text-xs text-[var(--muted)] mt-1"><i class="fa-solid fa-receipt mr-1"></i>mọi trạng thái</div></div>
    <div class="card p-5"><div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Doanh thu</div><div class="serif text-[30px] font-bold mt-1"><?php echo formatPrice($stats['revenue']); ?></div><div class="text-xs text-[var(--muted)] mt-1"><i class="fa-solid fa-coins mr-1"></i>đơn không bị hủy</div></div>
    <div class="card p-5"><div class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Sản phẩm</div><div class="serif text-[30px] font-bold mt-1"><?php echo $stats['products']; ?></div><div class="text-xs text-[var(--muted)] mt-1"><i class="fa-solid fa-box-open mr-1"></i>đang bán tại chợ</div></div>
  </div>

  <!-- Quản lý người dùng -->
  <div class="card mt-6 overflow-hidden">
    <div class="p-5 md:p-6 flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)]">
      <div>
        <h2 class="serif text-[22px]">Người dùng</h2>
        <p class="text-sm text-[var(--muted)] mt-1">Sửa thông tin, đổi quyền, đặt lại mật khẩu hoặc xóa tài khoản.</p>
      </div>
      <form method="get" class="flex gap-2">
        <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm email, tên, SĐT..." class="input w-[220px]">
        <button class="btn-terra px-5 text-sm font-semibold"><i class="fa-solid fa-magnifying-glass"></i></button>
        <?php if($q!==''): ?><a href="admin.php" class="btn-ghost px-4 text-sm grid place-items-center">Xóa lọc</a><?php endif; ?>
      </form>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full min-w-[860px]">
        <thead><tr><th>#</th><th>Người dùng</th><th>Liên hệ</th><th>Quyền</th><th>Đơn</th><th>Tham gia</th><th class="text-right">Thao tác</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td class="text-[var(--muted)]">#<?php echo $u['id']; ?></td>
            <td>
              <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center text-xs font-bold shrink-0"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($u['first_name'],0,1))); ?></span>
                <div class="min-w-0"><div class="font-medium truncate"><?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?></div><div class="text-xs text-[var(--muted)] truncate"><?php echo htmlspecialchars($u['email']); ?></div></div>
              </div>
            </td>
            <td><div><?php echo htmlspecialchars($u['phone']??'—'); ?></div><div class="text-xs text-[var(--muted)]"><?php echo htmlspecialchars(trim(($u['city']??''))!==''?$u['city']:'—'); ?></div></td>
            <td><span class="badge <?php echo $u['role']==='admin'?'bg-[var(--ink)] text-white':'bg-[var(--paper2)] text-[#5a4f43] border border-[var(--line)]'; ?>"><?php echo $u['role']==='admin'?'<i class="fa-solid fa-shield-halved text-[9px]"></i> Quản trị':'Khách hàng'; ?></span></td>
            <td class="font-medium"><?php echo (int)$u['order_count']; ?></td>
            <td class="text-[var(--muted)] text-xs"><?php echo date('d/m/Y',strtotime($u['created_at'])); ?></td>
            <td>
              <div class="flex items-center justify-end gap-1.5 flex-wrap">
                <button onclick='openEdit(<?php echo json_encode(["id"=>$u["id"],"fn"=>$u["first_name"],"ln"=>$u["last_name"],"phone"=>(string)($u["phone"]??""),"addr"=>(string)($u["address"]??""),"city"=>(string)($u["city"]??""),"email"=>$u["email"]], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)' class="pill px-3 py-1.5 text-xs hover:bg-[var(--paper2)]" title="Sửa thông tin"><i class="fa-solid fa-pen"></i></button>
                <form method="post" onsubmit="return confirm('Đặt lại mật khẩu cho #<?php echo $u['id']; ?>?')">
                  <?php echo csrfField(); ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="new_password" value="Matkhau@123">
                  <button class="pill px-3 py-1.5 text-xs hover:bg-[var(--paper2)]" title="Đặt lại mật khẩu (Matkhau@123)"><i class="fa-solid fa-key"></i></button>
                </form>
                <?php if($u['id']!=$_SESSION['user_id']): ?>
                <form method="post" onsubmit="return confirm('Chuyển quyền của #<?php echo $u['id']; ?> thành <?php echo $u['role']==='admin'?'khách hàng':'quản trị'; ?>?')">
                  <?php echo csrfField(); ?><input type="hidden" name="action" value="set_role"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="role" value="<?php echo $u['role']==='admin'?'user':'admin'; ?>">
                  <button class="pill px-3 py-1.5 text-xs hover:bg-[var(--paper2)]" title="<?php echo $u['role']==='admin'?'Hạ quyền':'Nâng quyền admin'; ?>"><i class="fa-solid fa-user-shield"></i></button>
                </form>
                <form method="post" onsubmit="return confirm('Xóa vĩnh viễn #<?php echo $u['id']; ?>? Hành động này không hoàn tác.')">
                  <?php echo csrfField(); ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <button class="pill px-3 py-1.5 text-xs text-[#7a3a20] hover:bg-[#fdf0e8]" title="Xóa"><i class="fa-regular fa-trash-can"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Đơn hàng gần đây -->
  <div class="card mt-6 overflow-hidden">
    <div class="p-5 md:p-6 border-b border-[var(--line)]">
      <h2 class="serif text-[22px]">Đơn hàng gần đây</h2>
      <p class="text-sm text-[var(--muted)] mt-1">12 đơn mới nhất — đổi trạng thái trực tiếp.</p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full min-w-[820px]">
        <thead><tr><th>#</th><th>Khách</th><th>Địa chỉ</th><th>Món</th><th>Tổng</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($orders)): ?><tr><td colspan="8" class="text-center text-[var(--muted)] py-10">Chưa có đơn hàng nào.</td></tr>
        <?php else: foreach($orders as $o): ?>
          <tr>
            <td class="text-[var(--muted)]">#<?php echo str_pad($o['id'],5,'0',STR_PAD_LEFT); ?></td>
            <td><div class="font-medium"><?php echo htmlspecialchars($o['first_name'].' '.$o['last_name']); ?></div><div class="text-xs text-[var(--muted)]"><?php echo htmlspecialchars($o['email']); ?></div></td>
            <td class="max-w-[220px]"><div class="truncate"><?php echo htmlspecialchars($o['shipping_address']); ?></div><div class="text-xs text-[var(--muted)]"><?php echo htmlspecialchars($o['phone']); ?></div></td>
            <td><?php echo (int)$o['item_count']; ?> món</td>
            <td class="serif font-bold"><?php echo formatPrice($o['total_price']); ?></td>
            <td class="text-xs"><?php echo $o['payment_method']==='cod'?'COD':htmlspecialchars($o['payment_method']); ?></td>
            <td>
              <span class="badge" style="background:<?php echo $statusColor[$o['status']]??'#e8b84a'; ?>22;color:<?php echo $statusColor[$o['status']]??'#8a6d1f'; ?>;border:1px solid <?php echo $statusColor[$o['status']]??'#e8b84a'; ?>55">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $statusColor[$o['status']]??'#e8b84a'; ?>"></span><?php echo htmlspecialchars($o['status']); ?>
              </span>
            </td>
            <td>
              <form method="post" class="flex gap-1.5 justify-end">
                <?php echo csrfField(); ?><input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                <select name="status" class="input !h-9 !w-[130px] text-xs">
                  <?php foreach(['Pending','Processing','Shipping','Completed','Cancelled'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $o['status']===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?>
                </select>
                <button class="pill px-3 text-xs hover:bg-[var(--paper2)]"><i class="fa-solid fa-check"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="mt-8 border-t border-[var(--line)] bg-[#fdf8f1]">
  <div class="max-w-[1280px] mx-auto px-4 py-8 text-center text-xs text-[var(--muted)]">© <?php echo date('Y'); ?> Chợ Gia Dụng · Khu vực quản trị</div>
</footer>

<!-- Dialog sửa thông tin user -->
<dialog id="editDialog">
  <form method="post" class="p-6 bg-white rounded-[20px]" id="editForm">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="edit_user">
    <input type="hidden" name="user_id" id="eId">
    <div class="flex items-start justify-between gap-4">
      <div><h3 class="serif text-[20px]">Sửa thông tin</h3><div class="text-xs text-[var(--muted)] mt-1" id="eEmail"></div></div>
      <button type="button" onclick="document.getElementById('editDialog').close()" class="pill w-9 h-9 grid place-items-center"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="grid grid-cols-2 gap-3 mt-5">
      <div><label class="label">Họ *</label><input name="first_name" id="eFn" required class="input mt-1"></div>
      <div><label class="label">Tên *</label><input name="last_name" id="eLn" required class="input mt-1"></div>
    </div>
    <div class="grid grid-cols-2 gap-3 mt-3">
      <div><label class="label">Điện thoại</label><input name="phone" id="ePhone" class="input mt-1"></div>
      <div><label class="label">Tỉnh / Thành</label><input name="city" id="eCity" class="input mt-1"></div>
    </div>
    <div class="mt-3"><label class="label">Địa chỉ</label><input name="address" id="eAddr" class="input mt-1"></div>
    <button class="btn-terra w-full py-3 text-sm font-semibold mt-5">Lưu thay đổi</button>
  </form>
</dialog>

<script>
function openEdit(u){
  document.getElementById('eId').value=u.id;
  document.getElementById('eEmail').textContent=u.email;
  document.getElementById('eFn').value=u.fn;
  document.getElementById('eLn').value=u.ln;
  document.getElementById('ePhone').value=u.phone;
  document.getElementById('eAddr').value=u.addr;
  document.getElementById('eCity').value=u.city;
  document.getElementById('editDialog').showModal();
}
</script>
</body>
</html>
