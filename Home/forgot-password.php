<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(isLoggedIn()){ header('Location: index.php'); exit; }

/*
 * Đặt lại mật khẩu bằng OTP qua email — 2 bước:
 *   Bước 1: nhập email → hệ thống gửi mã 6 số (hết hạn 10 phút, sai 5 lần khóa)
 *   Bước 2: nhập mã + mật khẩu mới → cập nhật users.password
 * Trạng thái bước nào được lưu trong $_SESSION['reset_email'].
 */

$error=''; $success=''; $step = empty($_SESSION['reset_email']) ? 1 : 2;

if(isset($_GET['cancel'])){
    unset($_SESSION['reset_email']);
    header('Location: forgot-password.php'); exit;
}

/* ---------- Gửi OTP (dùng chung cho lần đầu và gửi lại) ---------- */
function issueResetOtp(PDO $pdo, array $user): bool {
    // Giới hạn 3 lần gửi / 15 phút / user
    $recent = $pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE user_id=? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    $recent->execute([$user['id']]);
    if((int)$recent->fetchColumn() >= 3) return false;

    $otp   = (string)random_int(100000, 999999);
    $hash  = hash('sha256', $otp);
    // Vô hiệu mọi mã cũ đang chờ của user này
    $pdo->prepare("UPDATE password_resets SET used=1 WHERE user_id=? AND used=0")->execute([$user['id']]);
    $ins = $pdo->prepare("INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (?,?,?)");
    $ins->execute([$user['id'], $hash, date('Y-m-d H:i:s', time()+600)]);

    return sendEmail(
        $user['email'],
        'Mã đặt lại mật khẩu — Chợ Gia Dụng',
        emailWrap(
            'Đặt lại mật khẩu',
            '<p style="font-size:14px;color:#3a332d;line-height:22px">Chúng mình nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhập mã này ở trang đặt lại (hiệu lực <b>10 phút</b>):</p>'
            .'<div style="background:#fdf8f1;border:1px solid #eadfd1;border-radius:14px;text-align:center;padding:18px;margin:18px 0"><span style="font-size:32px;letter-spacing:10px;font-weight:bold;color:#c45b2f">'.htmlspecialchars($otp).'</span></div>'
            .'<p style="font-size:13px;color:#7a6e60">Không phải bạn yêu cầu? Cứ bỏ qua email — mật khẩu hiện tại vẫn giữ nguyên. Đừng chia sẻ mã này với ai, kể cả người tự xưng là nhân viên.</p>'
        )
    );
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrfCheck();
    $action = $_POST['action'] ?? '';

    /* ---------- Bước 1: yêu cầu mã ---------- */
    if($action==='request_email'){
        $email = trim($_POST['email'] ?? '');
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = 'Email chưa đúng định dạng.';
        } else {
            $st = $pdo->prepare("SELECT * FROM users WHERE email=?");
            $st->execute([$email]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
            if(!$user){
                // Không tiết lộ email có tồn tại hay không
                $success = 'Nếu email này đã đăng ký, mã OTP đang trên đường tới hộp thư của bạn.';
            } elseif(!issueResetOtp($pdo, $user)){
                $error = 'Bạn đã yêu cầu quá nhiều mã trong 15 phút. Thử lại sau nhé.';
            } else {
                $_SESSION['reset_email'] = $user['email'];
                $step = 2;
                $success = 'Mã OTP 6 số đã gửi tới <b>'.maskEmail($user['email']).'</b> — kiểm tra cả hộp Spam nhé (hiệu lực 10 phút).';
            }
        }
    }

    /* ---------- Bước 2: xác thực mã + đặt mật khẩu mới ---------- */
    if($action==='verify_reset'){
        $email = $_SESSION['reset_email'] ?? '';
        $otp   = preg_replace('/\D/','', $_POST['otp'] ?? '');
        $new   = $_POST['new_password'] ?? '';
        $conf  = $_POST['confirm_password'] ?? '';

        if($email===''){
            $error = 'Phiên đã hết. Hãy bắt đầu lại từ đầu.';
        } elseif(strlen($otp)!==6){
            $error = 'Mã OTP gồm 6 chữ số.';
        } elseif(strlen($new)<8 || !preg_match('/[a-z]/',$new) || !preg_match('/[A-Z]/',$new) || !preg_match('/[0-9]/',$new)){
            $error = 'Mật khẩu mới cần tối thiểu 8 ký tự, có chữ hoa, thường và số.';
        } elseif($new!==$conf){
            $error = 'Xác nhận mật khẩu chưa khớp.';
        } else {
            $st = $pdo->prepare("SELECT * FROM users WHERE email=?");
            $st->execute([$email]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
            if(!$user){
                unset($_SESSION['reset_email']);
                $error = 'Tài khoản không còn tồn tại. Hãy bắt đầu lại.';
            } else {
                $row = $pdo->prepare("SELECT * FROM password_resets WHERE user_id=? AND used=0 ORDER BY id DESC LIMIT 1");
                $row->execute([$user['id']]);
                $reset = $row->fetch(PDO::FETCH_ASSOC);

                if(!$reset){
                    $error = 'Không có mã nào đang chờ. Hãy yêu cầu mã mới.';
                } elseif((int)$reset['attempts'] >= 5){
                    $error = 'Bạn nhập sai quá 5 lần. Hãy yêu cầu mã mới.';
                } elseif(strtotime($reset['expires_at']) < time()){
                    $error = 'Mã đã hết hạn (10 phút). Hãy yêu cầu mã mới.';
                } elseif(!hash_equals($reset['otp_hash'], hash('sha256', $otp))){
                    $pdo->prepare("UPDATE password_resets SET attempts=attempts+1 WHERE id=?")->execute([$reset['id']]);
                    $left = 5 - (int)$reset['attempts'] - 1;
                    $error = 'Mã chưa đúng. Còn '.$left.' lần thử.';
                } else {
                    $pdo->beginTransaction();
                    try{
                        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
                        $pdo->prepare("UPDATE password_resets SET used=1 WHERE id=?")->execute([$reset['id']]);
                        $pdo->commit();
                        sendEmail($user['email'], 'Mật khẩu đã được đặt lại — Chợ Gia Dụng',
                            emailWrap('Đổi mật khẩu thành công',
                            '<p style="font-size:14px;color:#3a332d">Mật khẩu tài khoản <b>'.htmlspecialchars($user['email']).'</b> vừa được đặt lại. Nếu không phải bạn, liên hệ ngay info@chopgiadung.vn.</p>'));
                        unset($_SESSION['reset_email']);
                        header('Location: login.php?reset=1'); exit;
                    }catch(PDOException $e){
                        $pdo->rollBack();
                        $error = 'Lỗi: '.$e->getMessage();
                    }
                }
            }
        }
    }
}

$step = empty($_SESSION['reset_email']) ? ($step===2 ? 1 : $step) : 2;
$displayName = getDisplayName();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Đặt lại mật khẩu — Chợ Gia Dụng</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,600;9..144,0,700;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--paper:#fdf8f1;--paper2:#f5ece0;--line:#eadfd1;--ink:#1c1916;--muted:#7a6e60;--terracotta:#c45b2f;--terracotta2:#a84522;--cream:#fffaf3}
*{font-family:'Inter',system-ui,sans-serif}
.serif{font-family:'Fraunces',serif;letter-spacing:-.02em}
body{background:var(--paper);color:var(--ink)}
.card{background:white;border:1px solid var(--line);border-radius:24px}
.input{border:1px solid var(--line);background:var(--cream);border-radius:999px;height:44px;padding:0 16px;outline:none;width:100%;font-size:14px}
.input:focus{border-color:#d8cabd;box-shadow:0 0 0 4px rgba(232,220,200,.45);background:white}
.otp-input{letter-spacing:14px;text-align:center;font-size:24px;font-weight:600;font-family:'Fraunces',serif}
.btn-terra{background:var(--terracotta);color:white;border-radius:999px;transition:.2s}
.btn-terra:hover{background:var(--terracotta2);transform:translateY(-1px)}
.pill{border:1px solid var(--line);background:var(--cream);border-radius:999px}
.label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
</style>
</head>
<body class="min-h-screen flex flex-col antialiased">
<header class="border-b border-[var(--line)] bg-[rgba(253,248,241,.92)] backdrop-blur sticky top-0 z-40">
  <div class="max-w-[1160px] mx-auto px-4 py-4 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-[#1c1916] text-[#fdf8f1] grid place-items-center font-bold">cg</span><span class="serif text-[18px] font-bold">Chợ Gia Dụng</span></a>
    <a href="login.php" class="text-sm hover:underline underline-offset-4">← Về đăng nhập</a>
  </div>
</header>

<div class="flex-1 grid lg:grid-cols-[1.05fr_.95fr] max-w-[1160px] mx-auto w-full">
  <div class="hidden lg:flex flex-col justify-center px-10 py-12">
    <div class="inline-flex items-center gap-2 pill px-3 py-1.5 text-xs w-fit"><i class="fa-solid fa-shield-halved text-[10px]"></i> Xác thực 2 bước qua email</div>
    <h1 class="serif text-[40px] leading-[.95] mt-5">Quên mật khẩu là chuyện<br><span class="italic font-normal">nhỏ.</span></h1>
    <p class="text-sm text-[var(--muted)] leading-6 mt-4 max-w-[42ch]">Nhập email, tụi mình gửi mã 6 số. Mã sống 10 phút, sai 5 lần là tự khóa — an toàn kể cả khi ai đó đoán hộp thư của bạn.</p>
    <div class="mt-8 grid gap-3">
      <div class="card p-4 flex gap-3 items-center"><span class="w-10 h-10 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center serif font-bold">1</span><div><div class="text-sm font-medium">Nhập email đã đăng ký</div><div class="text-xs text-[var(--muted)]">Mã OTP gửi tới đúng email đó</div></div></div>
      <div class="card p-4 flex gap-3 items-center"><span class="w-10 h-10 rounded-full bg-[var(--paper2)] border border-[var(--line)] grid place-items-center serif font-bold">2</span><div><div class="text-sm font-medium">Nhập mã + mật khẩu mới</div><div class="text-xs text-[var(--muted)]">8+ ký tự, có chữ hoa và số</div></div></div>
      <div class="bg-[var(--ink)] text-[#fdf8f1] rounded-2xl p-5 mt-2">
        <div class="text-sm font-medium">“Nhận mã trong khoảng 1 phút. Không thấy thì lục lại mục Spam hoặc Quảng cáo nhé.”</div>
        <div class="text-xs text-white/60 mt-2">— Đội Chợ Gia Dụng</div>
      </div>
    </div>
  </div>

  <div class="px-4 py-8 lg:py-12 lg:pl-8 flex flex-col justify-center">
    <div class="card p-6 md:p-8">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="serif text-[26px] leading-none">Đặt lại mật khẩu</h2>
          <p class="text-sm text-[var(--muted)] mt-2"><?php echo $step===1 ? 'Bước 1 trên 2 — nhập email của bạn.' : 'Bước 2 trên 2 — nhập mã OTP và mật khẩu mới.'; ?></p>
        </div>
        <span class="hidden md:inline-flex items-center gap-1.5 pill px-3 py-1.5 text-xs"><span class="w-1.5 h-1.5 rounded-full bg-[#2e7d32]"></span> Bước <?php echo $step; ?>/2</span>
      </div>

      <?php if($error): ?><div class="mt-5 bg-[#fdf0e8] border border-[#f0d0c0] text-[#7a3a20] px-4 py-3 rounded-2xl text-sm"><?php echo $error; ?></div><?php endif; ?>
      <?php if($success): ?><div class="mt-5 bg-[#eef3ea] border border-[#cde0c7] text-[#2e5937] px-4 py-3 rounded-2xl text-sm"><?php echo $success; ?></div><?php endif; ?>

      <?php if($step===1): ?>
      <!-- BƯỚC 1: EMAIL -->
      <form method="post" class="mt-6 space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="request_email">
        <div><label class="label">Email đăng ký</label><input type="text" name="email" required placeholder="ban@email.com" autofocus class="input mt-1"></div>
        <button class="btn-terra w-full py-3.5 text-sm font-semibold mt-2"><i class="fa-regular fa-paper-plane mr-2"></i>Gửi mã OTP về email</button>
        <div class="text-center text-sm text-[var(--muted)]">Vừa nhớ ra mật khẩu? <a href="login.php" class="font-semibold text-[var(--ink)] underline underline-offset-4">Đăng nhập thôi</a></div>
      </form>
      <?php else: ?>
      <!-- BƯỚC 2: OTP + MẬT KHẨU MỚI -->
      <form method="post" class="mt-6 space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="verify_reset">
        <div class="bg-[var(--paper)] border border-[var(--line)] rounded-2xl px-4 py-3 text-sm flex items-center gap-2">
          <i class="fa-regular fa-envelope text-[var(--muted)]"></i>
          <span>Mã gửi tới <b><?php echo maskEmail($_SESSION['reset_email']); ?></b></span>
          <a href="forgot-password.php?cancel=1" class="ml-auto text-xs underline underline-offset-4 text-[var(--terracotta)]">Đổi email</a>
        </div>
        <div><label class="label">Mã OTP (6 số)</label><input name="otp" required inputmode="numeric" maxlength="6" placeholder="••••••" class="input mt-1 otp-input"></div>
        <div><label class="label">Mật khẩu mới</label><input type="password" name="new_password" required placeholder="8+ ký tự, có hoa/thường/số" class="input mt-1"></div>
        <div><label class="label">Nhập lại mật khẩu mới</label><input type="password" name="confirm_password" required placeholder="••••••••" class="input mt-1"></div>
        <button class="btn-terra w-full py-3.5 text-sm font-semibold mt-2">Đặt lại mật khẩu</button>
      </form>
      <form method="post" class="text-center mt-3">
        <?php echo csrfField(); ?><input type="hidden" name="action" value="request_email"><input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['reset_email']); ?>">
        <button class="text-xs underline underline-offset-4 text-[var(--muted)] hover:text-[var(--ink)]">Không nhận được mã? Gửi lại</button>
      </form>
      <?php endif; ?>

      <div class="text-xs text-center text-[var(--muted)] mt-5">Mã bảo mật được lưu dạng băm, không ai đọc được · Tối đa 3 lần gửi / 15 phút</div>
    </div>
    <div class="text-center text-xs text-[var(--muted)] mt-4">© 2024 Chợ Gia Dụng · Gốm — Gang — Gỗ</div>
  </div>
</div>
</body>
</html>
