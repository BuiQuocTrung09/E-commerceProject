<?php
require_once 'db.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(isLoggedIn()){ header('Location: index.php'); exit; }

$error=''; $success='';
if(isset($_GET['registered'])) $success='Đăng ký thành công! Vui lòng đăng nhập.';
if(isset($_GET['checkout']) && !isLoggedIn()) $error='Vui lòng đăng nhập để thanh toán.';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??'';
    $remember=isset($_POST['rememberMe']);
    if($email===''||$password===''){
        $error='Vui lòng nhập email và mật khẩu.';
    } else {
        try{
            $stmt=$pdo->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email=?");
            $stmt->execute([$email]);
            $user=$stmt->fetch(PDO::FETCH_ASSOC);
            if($user && password_verify($password,$user['password'])){
                $_SESSION['user_id']=$user['id'];
                $_SESSION['first_name']=$user['first_name'];
                $_SESSION['last_name']=$user['last_name'];
                $_SESSION['email']=$user['email'];
                if($remember){
                    // set a simple feedback via cookie (JS will store)
                }
                header('Location: '.($_GET['redirect']??'index.php'));
                exit;
            } else {
                $error='Email hoặc mật khẩu không chính xác.';
            }
        }catch(PDOException $e){
            $error='Lỗi hệ thống: '.$e->getMessage();
        }
    }
}
$rememberedEmail=$_COOKIE['rememberedEmail']??'';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Nhà Bếp & Đồ Gia Dụng Online</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;}
        .container{display:flex;max-width:1200px;width:100%;gap:40px;align-items:center;}
        .brand-section{flex:1;color:white;display:none;}
        @media(min-width:768px){.brand-section{display:block;}}
        .brand-section h1{font-size:48px;margin-bottom:20px;font-weight:bold;}
        .brand-section p{font-size:18px;margin-bottom:30px;line-height:1.6;opacity:0.9;}
        .benefits{list-style:none;}
        .benefits li{font-size:16px;margin-bottom:15px;display:flex;align-items:center;}
        .benefits li:before{content:"★";margin-right:12px;font-size:20px;color:#ffd700;}
        .login-card{background:white;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.3);padding:40px;width:100%;max-width:450px;}
        .login-header{text-align:center;margin-bottom:30px;}
        .login-header h2{font-size:32px;color:#333;margin-bottom:10px;font-weight:bold;}
        .login-header p{color:#666;font-size:14px;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;color:#333;font-weight:500;font-size:14px;}
        .form-group input{width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;transition:all 0.3s ease;font-family:inherit;}
        .form-group input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
        .remember-forgot{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;font-size:13px;}
        .remember-forgot input[type="checkbox"]{margin-right:6px;cursor:pointer;}
        .remember-forgot label{margin:0;cursor:pointer;display:flex;align-items:center;}
        .forgot-password a{color:#667eea;text-decoration:none;transition:color 0.3s ease;}
        .forgot-password a:hover{color:#764ba2;}
        .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;transition:transform 0.2s ease,box-shadow 0.2s ease;}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.3);}
        .btn-login:active{transform:translateY(0);}
        .btn-login:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
        .divider{display:flex;align-items:center;margin:30px 0;color:#999;}
        .divider::before,.divider::after{content:"";flex:1;height:1px;background:#e0e0e0;}
        .divider span{padding:0 10px;font-size:13px;}
        .social-login{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .social-btn{padding:12px;border:2px solid #e0e0e0;border-radius:6px;background:white;font-size:14px;font-weight:500;cursor:pointer;transition:all 0.3s ease;}
        .social-btn:hover{border-color:#667eea;color:#667eea;}
        .register-link{text-align:center;margin-top:20px;font-size:14px;color:#666;}
        .register-link a{color:#667eea;text-decoration:none;font-weight:600;}
        .register-link a:hover{text-decoration:underline;}
        .error-message{background:#fee;color:#c00;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px;}
        .success-message{background:#efe;color:#060;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px;}
        .login-info{background:#f5f5f5;padding:15px;border-radius:6px;margin-bottom:20px;font-size:12px;color:#666;line-height:1.6;}
        .login-info strong{color:#333;}
        @media(max-width:767px){.login-card{padding:25px;}.login-header h2{font-size:24px;}.remember-forgot{flex-direction:column;align-items:flex-start;gap:10px;}.login-info{display:none;}}
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-section">
            <h1>🏠 Nhà Bếp & Đồ Gia Dụng</h1>
            <p>Chào mừng trở lại! Khám phá những sản phẩm mới và ưu đãi độc quyền cho bạn.</p>
            <ul class="benefits">
                <li>Truy cập nhanh chóng vào đơn hàng của bạn</li>
                <li>Lưu các sản phẩm yêu thích</li>
                <li>Nhận ưu đãi và khuyến mãi độc quyền</li>
                <li>Theo dõi vận chuyển hàng hóa của bạn</li>
                <li>Lịch sử mua hàng và thanh toán</li>
            </ul>
        </div>
        <div class="login-card">
            <div class="login-header"><h2>Đăng Nhập</h2><p>Quản lý tài khoản và đơn hàng của bạn</p></div>
            <div class="login-info"><strong>🔒 Thông tin bảo mật:</strong><br>Tài khoản của bạn được bảo vệ bằng mã hóa. Vui lòng không chia sẻ mật khẩu.</div>
            <?php if($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <form method="post" id="loginForm">
                <div class="form-group"><label for="email">Email</label><input type="text" id="email" name="email" placeholder="your.email@example.com" required autofocus value="<?php echo htmlspecialchars($_POST['email']??$rememberedEmail); ?>"></div>
                <div class="form-group"><label for="password">Mật Khẩu</label><input type="password" id="password" name="password" placeholder="••••••••" required></div>
                <div class="remember-forgot">
                    <label><input type="checkbox" id="rememberMe" name="rememberMe"> Nhớ tôi</label>
                    <div class="forgot-password"><a href="#" onclick="document.getElementById('forgotPasswordModal').style.display='flex'; return false;">Quên mật khẩu?</a></div>
                </div>
                <button type="submit" class="btn-login">Đăng Nhập</button>
            </form>
            <div class="divider"><span>hoặc</span></div>
            <div class="social-login"><button type="button" class="social-btn" onclick="alert('Đang phát triển')">📘 Facebook</button><button type="button" class="social-btn" onclick="alert('Đang phát triển')">📧 Google</button></div>
            <div class="register-link">Chưa có tài khoản? <a href="register.php">Đăng Ký Ngay</a></div>
            <div style="text-align:center;margin-top:12px"><a href="index.php" style="font-size:13px;color:#666;">← Về trang chủ</a></div>
        </div>
    </div>
    <div id="forgotPasswordModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:1000;">
        <div style="background:white;padding:30px;border-radius:10px;max-width:400px;width:90%;">
            <h3 style="margin-bottom:20px;color:#333;">Đặt Lại Mật Khẩu</h3>
            <p style="color:#666;margin-bottom:20px;font-size:14px;">Nhập email của bạn và chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu (demo)</p>
            <input type="email" id="resetEmail" placeholder="your.email@example.com" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:6px;margin-bottom:15px;font-size:14px;">
            <div style="display:flex;gap:10px;">
                <button onclick="document.getElementById('forgotPasswordModal').style.display='none'" style="flex:1;padding:12px;border:2px solid #e0e0e0;background:white;border-radius:6px;cursor:pointer;font-weight:500;">Hủy</button>
                <button onclick="const v=document.getElementById('resetEmail').value.trim(); if(!v){alert('Vui lòng nhập email');return;} alert('Đã gửi hướng dẫn tới '+v+' (demo)'); document.getElementById('forgotPasswordModal').style.display='none';" style="flex:1;padding:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;">Gửi</button>
            </div>
        </div>
    </div>
    <script>
        // remember email via localStorage
        const emailInput=document.getElementById('email');
        const rememberChk=document.getElementById('rememberMe');
        const saved=localStorage.getItem('rememberedEmail');
        if(saved && !emailInput.value){ emailInput.value=saved; rememberChk.checked=true; }
        document.getElementById('loginForm').addEventListener('submit',function(){
            if(rememberChk.checked) localStorage.setItem('rememberedEmail',emailInput.value.trim());
            else localStorage.removeItem('rememberedEmail');
        });
        document.getElementById('forgotPasswordModal').addEventListener('click',function(e){ if(e.target===this) this.style.display='none'; });
    </script>
</body>
</html>
