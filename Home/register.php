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
        $error='Vui lòng điền đầy đủ thông tin.';
    } elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error='Email không hợp lệ.';
    } elseif(!preg_match('/^[0-9]{9,11}$/',$phone)){
        $error='Số điện thoại phải có 9-11 chữ số.';
    } elseif(strlen($password)<8 || !preg_match('/[a-z]/',$password) || !preg_match('/[A-Z]/',$password) || !preg_match('/[0-9]/',$password)){
        $error='Mật khẩu phải ít nhất 8 ký tự, có chữ hoa, chữ thường và số.';
    } elseif($password!==$confirm){
        $error='Mật khẩu xác nhận không trùng khớp.';
    } elseif(!$terms){
        $error='Vui lòng chấp nhận Điều khoản & Điều kiện.';
    } else {
        try{
            $chk=$pdo->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if($chk->fetch()){
                $error='Email đã được đăng ký, vui lòng dùng email khác.';
            } else {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $stmt=$pdo->prepare("INSERT INTO users (first_name,last_name,email,phone,address,city,password) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$firstName,$lastName,$email,$phone,$address,$city,$hash]);
                $success='Đăng ký thành công! Đang chuyển tới trang đăng nhập...';
                // store names in localStorage via JS redirect; also clear old
                $old=['firstName'=>'','lastName'=>'','email'=>'','phone'=>'','address'=>'','city'=>''];
                header('Refresh: 2; URL=login.php?registered=1');
            }
        }catch(PDOException $e){
            $error='Lỗi hệ thống: '.$e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Nhà Bếp & Đồ Gia Dụng Online</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;}
        .container{display:flex;max-width:1200px;width:100%;gap:40px;align-items:center;}
        .brand-section{flex:1;color:white;display:none;}
        @media(min-width:768px){.brand-section{display:block;}}
        .brand-section h1{font-size:48px;margin-bottom:20px;font-weight:bold;}
        .brand-section p{font-size:18px;margin-bottom:30px;line-height:1.6;opacity:0.9;}
        .features{list-style:none;}
        .features li{font-size:16px;margin-bottom:15px;display:flex;align-items:center;}
        .features li:before{content:"✓";margin-right:12px;font-size:24px;color:#4ade80;}
        .register-card{background:white;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.3);padding:40px;width:100%;max-width:450px;}
        .register-card h2{font-size:32px;color:#333;margin-bottom:10px;font-weight:bold;}
        .register-card p{color:#666;margin-bottom:30px;font-size:14px;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;color:#333;font-weight:500;font-size:14px;}
        .form-group input,.form-group select{width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;transition:all 0.3s ease;font-family:inherit;}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
        .password-strength{margin-top:5px;height:4px;background:#e0e0e0;border-radius:2px;overflow:hidden;}
        .strength-bar{height:100%;width:0%;transition:width 0.3s ease,background-color 0.3s ease;}
        .checkbox-group{display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;}
        .checkbox-group input[type="checkbox"]{margin-top:4px;width:18px;height:18px;cursor:pointer;}
        .checkbox-group label{margin:0;font-size:13px;color:#666;line-height:1.4;cursor:pointer;}
        .checkbox-group a{color:#667eea;text-decoration:none;}
        .checkbox-group a:hover{text-decoration:underline;}
        .btn-register{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;transition:transform 0.2s ease,box-shadow 0.2s ease;}
        .btn-register:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.3);}
        .btn-register:active{transform:translateY(0);}
        .divider{display:flex;align-items:center;margin:30px 0;color:#999;}
        .divider::before,.divider::after{content:"";flex:1;height:1px;background:#e0e0e0;}
        .divider span{padding:0 10px;font-size:13px;}
        .social-login{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .social-btn{padding:12px;border:2px solid #e0e0e0;border-radius:6px;background:white;font-size:14px;font-weight:500;cursor:pointer;transition:all 0.3s ease;}
        .social-btn:hover{border-color:#667eea;color:#667eea;}
        .login-link{text-align:center;margin-top:20px;font-size:14px;color:#666;}
        .login-link a{color:#667eea;text-decoration:none;font-weight:600;}
        .login-link a:hover{text-decoration:underline;}
        .error-message{background:#fee;color:#c00;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px;}
        .success-message{background:#efe;color:#060;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px;}
        @media(max-width:767px){.register-card{padding:25px;}.register-card h2{font-size:24px;}.form-row{grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-section">
            <h1>🏠 Nhà Bếp & Đồ Gia Dụng</h1>
            <p>Khám phá bộ sưu tập đồ gia dụng chất lượng cao với giá tốt nhất thị trường!</p>
            <ul class="features">
                <li>Hàng hóa chính hãng, chất lượng tốt</li>
                <li>Giao hàng nhanh chóng toàn quốc</li>
                <li>Hỗ trợ khách hàng 24/7</li>
                <li>Đảm bảo hoàn tiền 100% nếu không hài lòng</li>
                <li>Ưu đãi độc quyền cho thành viên</li>
            </ul>
        </div>
        <div class="register-card">
            <h2>Đăng Ký</h2>
            <p>Tạo tài khoản để bắt đầu mua sắm</p>
            <?php if($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <form method="post" id="registerForm" novalidate>
                <div class="form-row">
                    <div class="form-group"><label for="firstName">Họ</label><input type="text" id="firstName" name="firstName" placeholder="Nguyễn" required value="<?php echo htmlspecialchars($old['firstName']); ?>"></div>
                    <div class="form-group"><label for="lastName">Tên</label><input type="text" id="lastName" name="lastName" placeholder="Văn A" required value="<?php echo htmlspecialchars($old['lastName']); ?>"></div>
                </div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" placeholder="your.email@example.com" required value="<?php echo htmlspecialchars($old['email']); ?>"></div>
                <div class="form-group"><label for="phone">Số Điện Thoại</label><input type="tel" id="phone" name="phone" placeholder="0987654321" required value="<?php echo htmlspecialchars($old['phone']); ?>"></div>
                <div class="form-group"><label for="address">Địa Chỉ</label><input type="text" id="address" name="address" placeholder="123 Đường ABC, Quận 1, TP.HCM" required value="<?php echo htmlspecialchars($old['address']); ?>"></div>
                <div class="form-group"><label for="city">Thành Phố/Tỉnh</label>
                    <select id="city" name="city" required>
                        <option value="">-- Chọn Thành Phố/Tỉnh --</option>
                        <option value="Hà Nội" <?php echo $old['city']==='Hà Nội'?'selected':''; ?>>Hà Nội</option>
                        <option value="TP. Hồ Chí Minh" <?php echo $old['city']==='TP. Hồ Chí Minh'?'selected':''; ?>>TP. Hồ Chí Minh</option>
                        <option value="Đà Nẵng" <?php echo $old['city']==='Đà Nẵng'?'selected':''; ?>>Đà Nẵng</option>
                        <option value="Hải Phòng" <?php echo $old['city']==='Hải Phòng'?'selected':''; ?>>Hải Phòng</option>
                        <option value="Cần Thơ" <?php echo $old['city']==='Cần Thơ'?'selected':''; ?>>Cần Thơ</option>
                        <option value="Khác" <?php echo $old['city']==='Khác'?'selected':''; ?>>Khác</option>
                    </select>
                </div>
                <div class="form-group"><label for="password">Mật Khẩu</label><input type="password" id="password" name="password" placeholder="••••••••" required><div class="password-strength"><div class="strength-bar"></div></div><small style="color:#999;display:block;margin-top:5px;">Tối thiểu 8 ký tự, chứa chữ hoa, chữ thường và số</small></div>
                <div class="form-group"><label for="confirmPassword">Xác Nhận Mật Khẩu</label><input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••" required></div>
                <div class="checkbox-group"><input type="checkbox" id="terms" name="terms" required><label for="terms">Tôi đồng ý với <a href="term.php" target="_blank">Điều Khoản & Điều Kiện</a> và <a href="term.php" target="_blank">Chính Sách Bảo Mật</a></label></div>
                <button type="submit" class="btn-register">Đăng Ký Ngay</button>
            </form>
            <div class="divider"><span>hoặc</span></div>
            <div class="social-login"><button type="button" class="social-btn" onclick="alert('Đang phát triển')">📘 Facebook</button><button type="button" class="social-btn" onclick="alert('Đang phát triển')">📧 Google</button></div>
            <div class="login-link">Đã có tài khoản? <a href="login.php">Đăng Nhập</a></div>
        </div>
    </div>
    <script>
        const passwordInput=document.getElementById('password');
        const strengthBar=document.querySelector('.strength-bar');
        if(passwordInput){
            passwordInput.addEventListener('input',function(){
                const p=this.value; let s=0;
                if(p.length>=8) s++; if(/[a-z]/.test(p)) s++; if(/[A-Z]/.test(p)) s++; if(/[0-9]/.test(p)) s++; if(/[^a-zA-Z0-9]/.test(p)) s++;
                const widths=[0,20,40,60,80,100]; const colors=['#ff4757','#ffa502','#ffd32a','#26de81','#2ed573'];
                strengthBar.style.width=widths[s]+'%'; strengthBar.style.backgroundColor=colors[s-1]||'#ff4757';
            });
        }
        <?php if($success): ?>
        setTimeout(()=>{ window.location.href='login.php?registered=1'; },1500);
        <?php endif; ?>
    </script>
</body>
</html>
