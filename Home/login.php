<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(isLoggedIn()){ header('Location: index.php'); exit; }
$error=''; $success='';
if(isset($_GET['registered'])) $success='Tạo tài khoản xong — mời bạn đăng nhập.';
if(isset($_GET['reset'])) $success='Đặt lại mật khẩu thành công — đăng nhập bằng mật khẩu mới nhé.';
if(isset($_GET['checkout']) && !isLoggedIn()) $error='Đăng nhập để tiếp tục thanh toán nhé.';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??'';
    if($email===''||$password===''){ $error='Vui lòng nhập email và mật khẩu.'; }
    else {
        try{
            $stmt=$pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email=?");
            $stmt->execute([$email]);
            $user=$stmt->fetch(PDO::FETCH_ASSOC);
            if($user && password_verify($password,$user['password'])){
                $_SESSION['user_id']=$user['id'];
                $_SESSION['first_name']=$user['first_name'];
                $_SESSION['last_name']=$user['last_name'];
                $_SESSION['email']=$user['email'];
                $_SESSION['role']=$user['role'] ?? 'user';
                header('Location: '.($_GET['redirect']??'index.php')); exit;
            } else { $error='Email hoặc mật khẩu chưa đúng.'; }
        }catch(PDOException $e){ $error='Lỗi: '.$e->getMessage(); }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Đăng nhập — Chợ Gia Dụng</title>
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
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:#a84522;transform:translateY(-1px)}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
</style>
</head>
<body class="min-h-screen flex flex-col">
<header class="border-b border-[var(--line)] bg-[rgba(253,248,241,.92)] backdrop-blur sticky top-0 z-40">
  <div class="max-w-[1160px] mx-auto px-4 py-4 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center font-bold">cg</span><span class="serif text-[18px] font-bold">Chợ Gia Dụng</span><span class="hidden md:inline text-xs tracking-[.14em] uppercase text-[var(--muted)] ml-2">Est. 2014</span></a>
    <a href="index.php" class="text-sm hover:underline underline-offset-4">← Về cửa hàng</a>
  </div>
</header>

<div class="flex-1 grid lg:grid-cols-[1.05fr_.95fr] max-w-[1160px] mx-auto w-full">
  <div class="hidden lg:flex flex-col justify-center px-10 py-12">
    <div class="inline-flex items-center gap-2 pill px-3 py-1.5 text-xs w-fit"><span class="w-2 h-2 bg-[#2e7d32] rounded-full"></span> 2.400+ khách đã tin chọn</div>
    <h1 class="serif text-[40px] leading-[.95] mt-5">Chào mừng<br>trở lại <span class="italic font-normal">bếp ấm.</span></h1>
    <p class="text-sm text-[var(--muted)] leading-6 mt-4 max-w-[42ch]">Đăng nhập để xem đơn hàng, lưu món yêu thích và nhận thư nhà mỗi tháng — chỉ một email, không spam.</p>
    <div class="mt-8 grid gap-3">
      <div class="card p-4 flex gap-3 items-center"><span class="w-10 h-10 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center"><i class="fa-solid fa-box-open text-sm"></i></span><div><div class="text-sm font-medium">Theo dõi đơn dễ dàng</div><div class="text-xs text-[var(--muted)]">Cập nhật giao hàng qua email & SMS</div></div></div>
      <div class="card p-4 flex gap-3 items-center"><span class="w-10 h-10 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center"><i class="fa-regular fa-heart text-sm"></i></span><div><div class="text-sm font-medium">Lưu món yêu thích</div><div class="text-xs text-[var(--muted)]">Tạo danh sách riêng cho căn bếp của bạn</div></div></div>
      <div class="bg-[var(--ink)] text-[#fdf8f1] rounded-2xl p-5 mt-2">
        <div class="text-sm font-medium">“Giao nhanh, gói giấy kraft rất xinh. Mình giữ lại làm giấy gói quà.”</div><div class="text-xs text-white/60 mt-2">— Minh Anh, Q.3</div>
      </div>
    </div>
  </div>

  <div class="px-4 py-8 lg:py-12 lg:pl-8 flex flex-col justify-center">
    <div class="card p-6 md:p-8">
      <div class="flex items-start justify-between gap-4">
        <div><h2 class="serif text-[26px] leading-none">Đăng nhập</h2><p class="text-sm text-[var(--muted)] mt-2">Dùng email bạn đã đăng ký.</p></div>
        <span class="hidden md:inline-flex pill px-3 py-1.5 text-xs"><i class="fa-solid fa-lock text-[10px] mr-1"></i> Bảo mật</span>
      </div>

      <?php if($error): ?><div class="mt-5 bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <?php if($success): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

      <form method="post" id="loginForm" class="mt-6 space-y-4">
        <div><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Email</label><input id="email" name="email" type="text" placeholder="ban@email.com" required autofocus value="<?php echo htmlspecialchars($_POST['email']??''); ?>" class="input mt-1"></div>
        <div><div class="flex items-center justify-between"><label class="text-xs tracking-[.08em] uppercase text-[var(--muted)]">Mật khẩu</label><button type="button" onclick="const i=document.getElementById('password'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'Hiện':'Ẩn'" class="text-xs underline underline-offset-4">Hiện</button></div><input id="password" name="password" type="password" placeholder="••••••••" required class="input mt-1"></div>
        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="rememberMe" name="rememberMe" class="accent-[var(--ink)]"> <span class="text-[var(--muted)]">Ghi nhớ email</span></label>
          <a href="forgot-password.php" class="underline underline-offset-4">Quên mật khẩu?</a>
        </div>
        <button class="btn-terra w-full py-3.5 text-sm font-semibold mt-2">Đăng nhập</button>
        <div class="text-center text-sm text-[var(--muted)]">Chưa có tài khoản? <a href="register.php" class="font-semibold text-[var(--ink)] underline underline-offset-4">Tạo tài khoản</a></div>
      </form>

      <div class="flex items-center gap-3 my-6"><span class="h-px bg-[var(--line)] flex-1"></span><span class="text-xs tracking-[.12em] uppercase text-[var(--muted)]">Hoặc</span><span class="h-px bg-[var(--line)] flex-1"></span></div>
      <div class="grid grid-cols-2 gap-3">
        <button type="button" onclick="alert('Sắp ra mắt')" class="pill h-[44px] flex items-center justify-center gap-2 text-sm font-medium bg-white hover:border-[#d8cabd]"><i class="fa-brands fa-google"></i> Google</button>
        <button type="button" onclick="alert('Sắp ra mắt')" class="pill h-[44px] flex items-center justify-center gap-2 text-sm font-medium bg-white hover:border-[#d8cabd]"><i class="fa-brands fa-facebook"></i> Facebook</button>
      </div>
      <div class="text-xs text-center text-[var(--muted)] mt-4">Bằng việc tiếp tục, bạn đồng ý với <a href="term.php" class="underline">điều khoản</a>.</div>
    </div>
    <div class="text-center text-xs text-[var(--muted)] mt-4">© 2024 Chợ Gia Dụng · Gốm — Gang — Gỗ</div>
  </div>
</div>

<script>
const emailInput=document.getElementById('email');
const rememberChk=document.getElementById('rememberMe');
const saved=localStorage.getItem('rememberedEmail');
if(saved && !emailInput.value){ emailInput.value=saved; rememberChk.checked=true; }
document.getElementById('loginForm').addEventListener('submit',()=>{ if(rememberChk.checked) localStorage.setItem('rememberedEmail',emailInput.value.trim()); else localStorage.removeItem('rememberedEmail'); });
</script>
</body>
</html>
