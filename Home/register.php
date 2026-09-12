<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(isLoggedIn()){ header('Location: index.php'); exit; }
$error=''; $success='';
$old=['firstName'=>'','lastName'=>'','email'=>'','phone'=>'','address'=>'','city'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $firstName=trim($_POST['firstName']??'');
    $lastName=trim($_POST['lastName']??'');
    $email=trim($_POST['email']??'');
    $phone=trim($_POST['phone']??'');
    $address=trim($_POST['address']??'');
    $city=trim($_POST['city']??'');
    $password=$_POST['password']??'';
    $confirm=$_POST['confirmPassword']??'';
    $terms=isset($_POST['terms']);
    $old=['firstName'=>$firstName,'lastName'=>$lastName,'email'=>$email,'phone'=>$phone,'address'=>$address,'city'=>$city];
    if($firstName===''||$lastName===''||$email===''||$phone===''||$address===''||$city===''){
        $error='Vui lòng điền đủ các ô có dấu *.';
    } elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error='Email chưa đúng định dạng.';
    } elseif(!preg_match('/^[0-9]{9,11}$/',$phone)){
        $error='Số điện thoại 9–11 số.';
    } elseif(strlen($password)<8 || !preg_match('/[a-z]/',$password) || !preg_match('/[A-Z]/',$password) || !preg_match('/[0-9]/',$password)){
        $error='Mật khẩu tối thiểu 8 ký tự, có chữ hoa, thường và số.';
    } elseif($password!==$confirm){
        $error='Xác nhận mật khẩu chưa khớp.';
    } elseif(!$terms){
        $error='Bạn cần đồng ý điều khoản.';
    } else {
        try{
            $chk=$pdo->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if($chk->fetch()){ $error='Email đã được đăng ký.'; }
            else {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $stmt=$pdo->prepare("INSERT INTO users (first_name,last_name,email,phone,address,city,password) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$firstName,$lastName,$email,$phone,$address,$city,$hash]);
                $success='Tạo tài khoản xong — đang đưa bạn tới đăng nhập...';
                $old=['firstName'=>'','lastName'=>'','email'=>'','phone'=>'','address'=>'','city'=>''];
                header('Refresh: 1.5; URL=login.php?registered=1');
            }
        }catch(PDOException $e){ $error='Lỗi: '.$e->getMessage(); }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Tạo tài khoản — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
body{background:var(--paper);color:var(--ink)}
.card{background:white;border:1px solid var(--line);border-radius:24px}
.input{border:1px solid var(--line);background:var(--cream);border-radius:999px;height:44px;padding:0 16px;outline:none;width:100%;font-size:14px}
.input:focus{border-color:#d8cabd;box-shadow:0 0 0 4px rgba(232,220,200,.45);background:white}
.input-area{border:1px solid var(--line);background:var(--cream);border-radius:18px;padding:12px 16px;outline:none;width:100%;font-size:14px}
.input-area:focus{border-color:#d8cabd;background:white}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:#a84522;transform:translateY(-1px)}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.strength{height:6px;background:var(--paper2);border-radius:999px;overflow:hidden;border:1px solid var(--line)}
.strength-bar{height:100%;width:0%;transition:.3s;border-radius:999px}
</style>
</head>
<body class="min-h-screen flex flex-col">
<header class="border-b border-[var(--line)] bg-[rgba(253,248,241,.92)] backdrop-blur sticky top-0 z-40">
  <div class="max-w-[1160px] mx-auto px-4 py-4 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center font-bold">cg</span><span class="serif text-[18px] font-bold">Chợ Gia Dụng</span></a>
    <div class="hidden md:flex items-center gap-3 text-sm"><span class="text-[var(--muted)]">Đã có tài khoản?</span><a href="login.php" class="pill px-4 py-2 bg-white font-medium hover:border-[#d8cabd]">Đăng nhập</a></div>
    <a href="index.php" class="md:hidden text-sm underline">Về cửa hàng</a>
  </div>
</header>

<div class="flex-1 grid lg:grid-cols-[.95fr_1.05fr] max-w-[1160px] mx-auto w-full">
  <div class="hidden lg:flex flex-col justify-center px-10 py-10">
    <div class="inline-flex pill px-3 py-1.5 text-xs w-fit"><span class="w-2 h-2 bg-[var(--terracotta)] rounded-full animate-pulse"></span> Thành viên mới — giảm 10% đơn đầu</div>
    <h1 class="serif text-[40px] leading-[.95] mt-5">Tạo tài khoản,<br><span class="italic font-normal">giữ bếp ấm lâu.</span></h1>
    <p class="text-sm text-[var(--muted)] leading-6 mt-4 max-w-[44ch]">Một tài khoản để lưu món yêu thích, theo dõi đơn và nhận thư nhà mỗi tháng. Tụi mình chỉ gửi khi có chuyện hay.</p>
    <div class="mt-8 space-y-3 text-sm">
      <div class="flex gap-3"><span class="w-8 h-8 rounded-full bg-white border border-[var(--line)] grid place-items-center text-xs shrink-0"><i class="fa-solid fa-gift"></i></span><div><div class="font-medium">Quà chào mừng</div><div class="text-xs text-[var(--muted)]">Mã giảm 10% gửi qua email sau khi đăng ký</div></div></div>
      <div class="flex gap-3"><span class="w-8 h-8 rounded-full bg-white border border-[var(--line)] grid place-items-center text-xs shrink-0"><i class="fa-solid fa-shield-halved"></i></span><div><div class="font-medium">Bảo mật</div><div class="text-xs text-[var(--muted)]">Mật khẩu mã hóa, không lưu thẻ</div></div></div>
      <div class="flex gap-3"><span class="w-8 h-8 rounded-full bg-white border border-[var(--line)] grid place-items-center text-xs shrink-0"><i class="fa-solid fa-leaf"></i></span><div><div class="font-medium">Bền & sửa được</div><div class="text-xs text-[var(--muted)]">Mua ít, dùng lâu — đó là cách tụi mình làm</div></div></div>
    </div>
    <div class="mt-8 bg-[var(--ink)] text-[#fdf8f1] rounded-2xl p-5">
      <div class="text-sm leading-6">“Mình thích cách shop gói hàng — giấy kraft, dây đay, không một miếng nilon. Mở ra đã thấy thơm mùi gỗ.”</div><div class="text-xs text-white/60 mt-3">— Quỳnh, Đà Nẵng</div>
    </div>
  </div>

  <div class="px-4 py-8 lg:py-10 lg:pl-8">
    <div class="card p-6 md:p-8">
      <h2 class="serif text-[26px] leading-none">Tạo tài khoản</h2><p class="text-sm text-[var(--muted)] mt-2">Chỉ mất một phút — và bạn có thể đặt hàng ngay.</p>

      <?php if($error): ?><div class="mt-5 bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <?php if($success): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

      <form method="post" class="mt-6 space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Họ *</label><input name="firstName" value="<?php echo htmlspecialchars($old['firstName']); ?>" placeholder="Nguyễn" required class="input mt-1"></div>
          <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Tên *</label><input name="lastName" value="<?php echo htmlspecialchars($old['lastName']); ?>" placeholder="Văn A" required class="input mt-1"></div>
        </div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Email *</label><input type="email" name="email" value="<?php echo htmlspecialchars($old['email']); ?>" placeholder="ban@email.com" required class="input mt-1"></div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Điện thoại *</label><input name="phone" value="<?php echo htmlspecialchars($old['phone']); ?>" placeholder="09xxxxxxxx" required class="input mt-1"></div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Địa chỉ *</label><input name="address" value="<?php echo htmlspecialchars($old['address']); ?>" placeholder="Số nhà, đường, phường..." required class="input mt-1"></div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Tỉnh / Thành *</label><select name="city" required class="input mt-1"><option value="">Chọn</option><option value="Hà Nội" <?php echo $old['city']==='Hà Nội'?'selected':''; ?>>Hà Nội</option><option value="TP. Hồ Chí Minh" <?php echo $old['city']==='TP. Hồ Chí Minh'?'selected':''; ?>>TP. Hồ Chí Minh</option><option value="Đà Nẵng" <?php echo $old['city']==='Đà Nẵng'?'selected':''; ?>>Đà Nẵng</option><option value="Hải Phòng" <?php echo $old['city']==='Hải Phòng'?'selected':''; ?>>Hải Phòng</option><option value="Cần Thơ" <?php echo $old['city']==='Cần Thơ'?'selected':''; ?>>Cần Thơ</option><option value="Khác" <?php echo $old['city']==='Khác'?'selected':''; ?>>Khác</option></select></div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Mật khẩu *</label><input id="password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required class="input mt-1"><div class="strength mt-2"><div class="strength-bar"></div></div><div class="text-xs text-[var(--muted)] mt-1">Gợi ý: 8+ ký tự, có hoa/thường/số</div></div>
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Nhập lại mật khẩu *</label><input type="password" name="confirmPassword" placeholder="••••••••" required class="input mt-1"></div>
        <label class="flex gap-3 text-sm leading-5"><input type="checkbox" name="terms" required class="mt-1 accent-[var(--ink)]"><span>Tôi đồng ý với <a href="term.php" class="underline font-medium">điều khoản</a> và cho phép liên hệ về đơn hàng.</span></label>
        <button class="btn-terra w-full py-3.5 text-sm font-semibold">Tạo tài khoản — nhận mã 10%</button>
        <div class="text-center text-sm text-[var(--muted)]">Đã có tài khoản? <a href="login.php" class="font-semibold text-[var(--ink)] underline underline-offset-4">Đăng nhập</a></div>
      </form>
    </div>
  </div>
</div>

<script>
const pw=document.getElementById('password');
const bar=document.querySelector('.strength-bar');
if(pw){
  pw.addEventListener('input',()=>{
    const p=pw.value; let s=0;
    if(p.length>=8) s++; if(/[a-z]/.test(p)) s++; if(/[A-Z]/.test(p)) s++; if(/[0-9]/.test(p)) s++; if(/[^A-Za-z0-9]/.test(p)) s++;
    const w=[0,20,40,60,80,100][s];
    const c=['#e7ddd0','#f0a66a','#e8b84a','#7fb069','#2e7d32'][s-1]||'#e7ddd0';
    bar.style.width=w+'%'; bar.style.background=c;
  });
}
</script>
</body>
</html>
