<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(!isLoggedIn()){ header('Location: login.php?redirect=profile.php'); exit; }

$me = $pdo->prepare("SELECT * FROM users WHERE id=?");
$me->execute([$_SESSION['user_id']]);
$user = $me->fetch(PDO::FETCH_ASSOC);
if(!$user){ header('Location: logout.php'); exit; }

$error=''; $success=''; $otpSent=false;

// Hủy luồng OTP đang chờ
if(isset($_GET['cancel_otp'])){ unset($_SESSION['otp_target']); header('Location: profile.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrfCheck();
    $action = $_POST['action'] ?? '';

    /* ---------- 1. Cập nhật thông tin cá nhân ---------- */
    if($action==='update_info'){
        $firstName=trim($_POST['first_name']??'');
        $lastName=trim($_POST['last_name']??'');
        $phone=trim($_POST['phone']??'');
        $address=trim($_POST['address']??'');
        $city=trim($_POST['city']??'');
        $gender=$_POST['gender']??'other';
        if(!in_array($gender,['male','female','other'])) $gender='other';

        if($firstName===''||$lastName===''){ $error='Vui lòng điền họ và tên.'; }
        elseif($phone!=='' && !preg_match('/^[0-9]{9,11}$/',$phone)){ $error='Số điện thoại 9–11 số.'; }
        else{
            try{
                $st=$pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,address=?,city=?,gender=? WHERE id=?");
                $st->execute([$firstName,$lastName,$phone,$address,$city,$gender,$user['id']]);
                $_SESSION['first_name']=$firstName; $_SESSION['last_name']=$lastName;
                $success='Đã lưu thông tin cá nhân.';
                $me->execute([$user['id']]); $user=$me->fetch(PDO::FETCH_ASSOC);
            }catch(PDOException $e){ $error='Lỗi: '.$e->getMessage(); }
        }
    }

    /* ---------- 2. Yêu cầu đổi mật khẩu -> gửi OTP ---------- */
    if($action==='request_otp'){
        $current=$_POST['current_password']??'';
        $new=$_POST['new_password']??'';
        $confirm=$_POST['confirm_password']??'';
        if(!password_verify($current,$user['password'])){
            $error='Mật khẩu hiện tại chưa đúng.';
        } elseif(strlen($new)<8 || !preg_match('/[a-z]/',$new) || !preg_match('/[A-Z]/',$new) || !preg_match('/[0-9]/',$new)){
            $error='Mật khẩu mới cần tối thiểu 8 ký tự, có chữ hoa, thường và số.';
        } elseif($new===$current){
            $error='Mật khẩu mới phải khác mật khẩu hiện tại.';
        } elseif($new!==$confirm){
            $error='Xác nhận mật khẩu mới chưa khớp.';
        } else {
            $otp=(string)random_int(100000,999999);
            $hash=hash('sha256',$otp);
            $expires=date('Y-m-d H:i:s', time()+600); // 10 phút
            // Vô hiệu OTP cũ của user này
            $pdo->prepare("UPDATE password_resets SET used=1 WHERE user_id=? AND used=0")->execute([$user['id']]);
            $ins=$pdo->prepare("INSERT INTO password_resets (user_id,otp_hash,expires_at) VALUES (?,?,?)");
            $ins->execute([$user['id'],$hash,$expires]);

            $sent=sendEmail(
                $user['email'],
                'Mã xác nhận đổi mật khẩu — Chợ Gia Dụng',
                emailWrap(
                    'Xác nhận đổi mật khẩu',
                    '<p style="font-size:14px;color:#3a332d;line-height:22px">Bạn vừa yêu cầu đổi mật khẩu trên Chợ Gia Dụng. Nhập mã này vào trang xác nhận (hiệu lực <b>10 phút</b>):</p>'
                    .'<div style="background:#fdf8f1;border:1px solid #eadfd1;border-radius:14px;text-align:center;padding:18px;margin:18px 0"><span style="font-size:32px;letter-spacing:10px;font-weight:bold;color:#c45b2f">'.htmlspecialchars($otp).'</span></div>'
                    .'<p style="font-size:13px;color:#7a6e60">Không ai trong tụi mình sẽ hỏi bạn mã này — đừng gửi cho ai, kể cả "nhân viên hỗ trợ".</p>'
                )
            );
            if($sent){
                $otpSent=true;
                $_SESSION['otp_target']='password';
                $success='Mã OTP 6 số đã được gửi tới <b>'.maskEmail($user['email']).'</b> — kiểm tra hộp thư (kể cả Spam) nhé.';
            } else {
                $error='Không gửi được email lúc này. Nếu bạn đang chạy trên máy cá nhân, xem mã thử nghiệm trong file <b>Home/otp_dev.log</b>.';
            }
        }
    }

    /* ---------- 3. Xác nhận OTP + mật khẩu mới ---------- */
    if($action==='verify_otp'){
        $otp=preg_replace('/\D/','',$_POST['otp']??'');
        $new=$_POST['new_password']??'';
        $confirm=$_POST['confirm_password']??'';
        if(strlen($otp)!==6){ $error='Mã OTP gồm 6 chữ số.'; }
        elseif(strlen($new)<8 || !preg_match('/[a-z]/',$new) || !preg_match('/[A-Z]/',$new) || !preg_match('/[0-9]/',$new)){
            $error='Mật khẩu mới cần tối thiểu 8 ký tự, có chữ hoa, thường và số.';
        } elseif($new!==$confirm){
            $error='Xác nhận mật khẩu mới chưa khớp.';
        } else {
            $row=$pdo->prepare("SELECT * FROM password_resets WHERE user_id=? AND used=0 ORDER BY id DESC LIMIT 1");
            $row->execute([$user['id']]);
            $reset=$row->fetch(PDO::FETCH_ASSOC);
            if(!$reset){ $error='Không có yêu cầu nào đang chờ. Hãy bấm "Gửi mã OTP" trước.'; }
            elseif($reset['attempts']>=5){ $error='Bạn đã nhập sai quá 5 lần. Yêu cầu mã mới nhé.'; }
            elseif(strtotime($reset['expires_at'])<time()){ $error='Mã đã hết hạn (10 phút). Yêu cầu mã mới nhé.'; }
            elseif(!hash_equals($reset['otp_hash'],hash('sha256',$otp))){
                $pdo->prepare("UPDATE password_resets SET attempts=attempts+1 WHERE id=?")->execute([$reset['id']]);
                $left=5-(int)$reset['attempts']-1;
                $error='Mã không đúng. Còn '.$left.' lần thử.';
            } else {
                $pdo->beginTransaction();
                try{
                    $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$user['id']]);
                    $pdo->prepare("UPDATE password_resets SET used=1 WHERE id=?")->execute([$reset['id']]);
                    $pdo->commit();
                    sendEmail($user['email'],'Mật khẩu đã được đổi — Chợ Gia Dụng',
                        emailWrap('Đổi mật khẩu thành công','<p style="font-size:14px;color:#3a332d">Mật khẩu tài khoản <b>'.htmlspecialchars($user['email']).'</b> vừa được đổi. Nếu không phải bạn, liên hệ ngay info@chopgiadung.vn.</p>'));
                    $success='Đổi mật khẩu thành công!';
                    $_SESSION['flash_success']='Đổi mật khẩu thành công — hãy dùng mật khẩu mới từ lần đăng nhập sau.';
                    header('Location: profile.php?pw=ok'); exit;
                }catch(PDOException $e){ $pdo->rollBack(); $error='Lỗi: '.$e->getMessage(); }
            }
        }
    }
}

$otpTarget = ($_SESSION['otp_target']??'')==='' ? '' : ($_SESSION['otp_target']??'');
$flashSuccess = $_SESSION['flash_success']??'';
unset($_SESSION['flash_success']);
$displayName=getDisplayName();
$cartCount=getCartCount();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Tài khoản của tôi — Chợ Gia Dụng</title>
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
.card{background:white;border:1px solid var(--line);border-radius:22px}
.input{border:1px solid var(--line);background:var(--cream);border-radius:999px;height:44px;padding:0 16px;outline:none;width:100%;font-size:14px}
.input:focus{border-color:#d8cabd;box-shadow:0 0 0 4px rgba(232,220,200,.45);background:white}
.otp-input{letter-spacing:14px;text-align:center;font-size:24px;font-weight:600;font-family:'Fraunces',serif}
.label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
</style>
</head>
<body class="antialiased">
<div class="topbar text-[11px] tracking-[.14em] uppercase"><div class="max-w-[1280px] mx-auto px-4 py-[10px] flex items-center justify-between"><span class="hidden md:inline">Gói giấy kraft — không nilon</span><span>Tài khoản · Thông tin & bảo mật</span><a href="shop.php" class="hidden md:inline hover:text-white/70">Tiếp tục chọn đồ →</a></div></div>
<header class="header sticky top-0 z-40">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-3 shrink-0"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center text-[15px] font-bold">cg</span><span class="serif text-[20px] font-bold">Chợ Gia Dụng</span></a>
    <nav class="hidden md:flex items-center gap-6 text-sm ml-6"><a href="index.php" class="hover:text-black/60">Trang chủ</a><a href="shop.php" class="hover:text-black/60">Chợ</a><a href="about.php" class="hover:text-black/60">Câu chuyện</a><a href="profile.php" class="font-semibold underline underline-offset-8">Tài khoản</a></nav>
    <div class="ml-auto flex items-center gap-2">
      <a href="profile.php" class="hidden md:inline-flex items-center gap-2 pill h-[42px] px-4 text-sm bg-white"><i class="fa-regular fa-user"></i><span class="max-w-[110px] truncate"><?php echo $displayName; ?></span></a>
      <a href="cart.php" class="relative pill h-[42px] px-4 flex items-center gap-2 text-sm font-medium"><i class="fa-solid fa-bag-shopping"></i><span class="hidden sm:inline">Giỏ</span><span class="bg-[var(--terracotta)] text-white text-[11px] font-bold min-w-[20px] h-5 grid place-items-center rounded-full px-1.5"><?php echo $cartCount; ?></span></a>
      <a href="logout.php" class="pill h-[42px] px-4 flex items-center text-sm text-[var(--terracotta)] font-medium hover:bg-white">Đăng xuất</a>
    </div>
  </div>
</header>

<div class="max-w-[1080px] mx-auto px-4 py-6">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <div class="text-[11px] tracking-[.16em] uppercase text-[var(--muted)]">Xin chào</div>
      <h1 class="serif text-[32px] md:text-[40px] leading-none mt-2"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></h1>
    </div>
    <div class="flex gap-2 text-sm">
      <a href="index.php" class="btn-ghost px-5 py-2.5">← Về cửa hàng</a>
      <?php if(isAdmin()): ?><a href="admin.php" class="pill px-5 py-2.5 bg-[var(--ink)] text-white border-[var(--ink)] font-medium"><i class="fa-solid fa-shield-halved mr-1 text-xs"></i>Quản trị</a><?php endif; ?>
    </div>
  </div>

  <?php if($flashSuccess): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($flashSuccess); ?></div><?php endif; ?>
  <?php if(isset($_GET['pw']) && $_GET['pw']==='ok' && !$flashSuccess): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm">Đổi mật khẩu thành công!</div><?php endif; ?>
  <?php if($error): ?><div class="mt-5 bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm"><?php echo $error; ?></div><?php endif; ?>
  <?php if($success): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo $success; ?></div><?php endif; ?>

  <div class="grid lg:grid-cols-[.9fr_1.1fr] gap-6 mt-6 items-start">

    <!-- Cột trái: hồ sơ ngắn -->
    <div class="card p-6">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-[var(--ink)] text-[#fdf8f1] grid place-items-center serif text-xl font-bold"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($user['first_name'],0,1))); ?></div>
        <div class="min-w-0">
          <div class="font-semibold truncate"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div>
          <div class="text-sm text-[var(--muted)] truncate"><?php echo htmlspecialchars($user['email']); ?></div>
          <span class="inline-flex pill px-2.5 py-0.5 text-[11px] mt-2 <?php echo $user['role']==='admin'?'bg-[var(--ink)] text-white border-[var(--ink)]':'bg-[var(--paper)]'; ?>"><?php echo $user['role']==='admin'?'Quản trị viên':'Khách hàng'; ?></span>
        </div>
      </div>
      <div class="h-px bg-[var(--line)] my-5"></div>
      <ul class="space-y-3 text-sm">
        <li class="flex items-center gap-3"><i class="fa-solid fa-phone text-[var(--muted)] w-4"></i><?php echo $user['phone']!==''&&$user['phone']!==null?htmlspecialchars($user['phone']):'<span class="text-[var(--muted)]">Chưa có số điện thoại</span>'; ?></li>
        <li class="flex items-start gap-3"><i class="fa-solid fa-location-dot text-[var(--muted)] w-4 mt-1"></i><span><?php echo trim(($user['address']??'').' '.($user['city']??''))!==''?htmlspecialchars(trim(($user['address']??'').', '.($user['city']??''))):'<span class="text-[var(--muted)]">Chưa có địa chỉ</span>'; ?></span></li>
        <li class="flex items-center gap-3"><i class="fa-regular fa-calendar text-[var(--muted)] w-4"></i>Tham gia <?php echo date('m/Y',strtotime($user['created_at'])); ?></li>
      </ul>
      <div class="mt-5 bg-[var(--paper)] border border-[var(--line)] rounded-2xl p-4 text-sm">
        <div class="font-medium flex items-center gap-2"><i class="fa-solid fa-lock text-xs"></i>Bảo mật tài khoản</div>
        <p class="text-xs text-[var(--muted)] leading-5 mt-1.5">Đổi mật khẩu cần mã OTP gửi về email — mã sống 10 phút, sai 5 lần là khóa.</p>
      </div>
    </div>

    <!-- Cột phải: 2 form -->
    <div class="space-y-6">

      <!-- Form thông tin cá nhân -->
      <div class="card p-6 md:p-7">
        <h2 class="serif text-[22px]">Thông tin cá nhân</h2>
        <p class="text-sm text-[var(--muted)] mt-1">Dùng để giao hàng và liên hệ khi có vấn đề với đơn.</p>
        <form method="post" class="mt-5 space-y-4">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="update_info">
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Họ *</label><input name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required class="input mt-1"></div>
            <div><label class="label">Tên *</label><input name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required class="input mt-1"></div>
          </div>
          <div><label class="label">Giới tính</label>
            <div class="flex gap-4 mt-1 text-sm">
              <label class="flex items-center gap-2"><input type="radio" name="gender" value="male" <?php echo $user['gender']==='male'?'checked':''; ?> class="accent-[var(--ink)]"> Nam</label>
              <label class="flex items-center gap-2"><input type="radio" name="gender" value="female" <?php echo $user['gender']==='female'?'checked':''; ?> class="accent-[var(--ink)]"> Nữ</label>
              <label class="flex items-center gap-2"><input type="radio" name="gender" value="other" <?php echo $user['gender']!=='male'&&$user['gender']!=='female'?'checked':''; ?> class="accent-[var(--ink)]"> Khác</label>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Điện thoại</label><input name="phone" value="<?php echo htmlspecialchars($user['phone']??''); ?>" placeholder="09xxxxxxxx" class="input mt-1"></div>
            <div><label class="label">Tỉnh / Thành</label>
              <select name="city" class="input mt-1">
                <option value="">Chọn</option>
                <?php foreach(['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','Khác'] as $c): ?>
                  <option value="<?php echo $c; ?>" <?php echo ($user['city']??'')===$c?'selected':''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div><label class="label">Địa chỉ</label><input name="address" value="<?php echo htmlspecialchars($user['address']??''); ?>" placeholder="Số nhà, đường, phường..." class="input mt-1"></div>
          <div class="text-xs text-[var(--muted)]">Email đăng nhập <b><?php echo htmlspecialchars($user['email']); ?></b> — không thể tự đổi, cần hỗ trợ thì nhắn shop.</div>
          <button class="btn-terra px-7 py-3 text-sm font-semibold">Lưu thay đổi</button>
        </form>
      </div>

      <!-- Form đổi mật khẩu -->
      <div class="card p-6 md:p-7">
        <h2 class="serif text-[22px]">Đổi mật khẩu</h2>
        <p class="text-sm text-[var(--muted)] mt-1">Xác thực 2 bước: nhập mật khẩu hiện tại → nhận mã OTP qua email → đặt mật khẩu mới.</p>

        <?php if(empty($otpTarget)): ?>
        <!-- Bước 1: yêu cầu OTP -->
        <form method="post" class="mt-5 space-y-4" id="formStep1">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="request_otp">
          <div><label class="label">Mật khẩu hiện tại *</label><input type="password" name="current_password" required placeholder="••••••••" class="input mt-1"></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Mật khẩu mới *</label><input type="password" name="new_password" id="newPw" required placeholder="8+ ký tự, có hoa/thường/số" class="input mt-1"></div>
            <div><label class="label">Nhập lại mật khẩu mới *</label><input type="password" name="confirm_password" required placeholder="••••••••" class="input mt-1"></div>
          </div>
          <div class="flex items-center justify-between flex-wrap gap-3">
            <button class="btn-terra px-7 py-3 text-sm font-semibold"><i class="fa-regular fa-paper-plane mr-2"></i>Gửi mã OTP về email</button>
            <span class="text-xs text-[var(--muted)]"><i class="fa-regular fa-envelope mr-1"></i>Gửi tới <?php echo maskEmail($user['email']); ?></span>
          </div>
        </form>
        <?php else: ?>
        <!-- Bước 2: nhập OTP -->
        <div class="mt-5 inline-flex items-center gap-2 pill px-4 py-2 text-sm bg-white">
          <span class="w-2 h-2 bg-[var(--terracotta)] rounded-full animate-pulse"></span>
          Đang chờ mã OTP — gửi tới <b><?php echo maskEmail($user['email']); ?></b>
          <a href="profile.php?cancel_otp=1" class="underline ml-2 text-[var(--terracotta)]">Hủy</a>
        </div>
        <form method="post" class="mt-5 space-y-4">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="verify_otp">
          <div><label class="label">Mã OTP (6 số, hiệu lực 10 phút)</label><input name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required placeholder="••••••" class="input otp-input mt-1"></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Mật khẩu mới *</label><input type="password" name="new_password" required placeholder="8+ ký tự" class="input mt-1"></div>
            <div><label class="label">Nhập lại mật khẩu mới *</label><input type="password" name="confirm_password" required placeholder="••••••••" class="input mt-1"></div>
          </div>
          <div class="text-xs text-[var(--muted)]">Sai quá 5 lần mã sẽ bị khóa — bấm "Gửi lại" ở bước 1 nếu cần.</div>
          <div class="flex gap-3">
            <button class="btn-terra px-7 py-3 text-sm font-semibold">Xác nhận & đổi mật khẩu</button>
            <a href="profile.php" class="btn-ghost px-6 py-3 text-sm">Quay lại</a>
          </div>
        </form>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<footer class="mt-8 border-t border-[var(--line)] bg-[#fdf8f1]">
  <div class="max-w-[1080px] mx-auto px-4 py-8 text-center text-xs text-[var(--muted)]">© <?php echo date('Y'); ?> Chợ Gia Dụng · <a href="term.php" class="underline">Điều khoản</a></div>
</footer>

<script>
// nút "Hủy" ở bước OTP mở lại bước 1 bằng cách hiện form ẩn — không cần vì form 1 bị thay thế; giữ đơn giản
document.querySelectorAll('.otp-input').forEach(inp=>{
  inp.addEventListener('input',()=>{ inp.value=inp.value.replace(/\D/g,'').slice(0,6); });
});
</script>
</body>
</html>
