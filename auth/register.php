<?php
require_once '../config/config/db.php';
session_start();
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $hash     = password_hash($password, PASSWORD_DEFAULT);
    $sql  = "INSERT INTO users (username, email, password_hash) VALUES (:u,:e,:h)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':u'=>$username,':e'=>$email,':h'=>$hash]);
        $success = "Account created! <a href='login.php' style='color:#a78bfa;font-weight:600;'>Sign in now →</a>";
    } catch (PDOException $e) {
        $error = str_contains($e->getMessage(),'Duplicate') ? "Username or email already exists." : "Error: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register — Portfolio Generator</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#24243e 100%);overflow:hidden;position:relative;}
body::before,body::after{content:'';position:fixed;border-radius:50%;filter:blur(80px);opacity:.3;animation:float 8s ease-in-out infinite;pointer-events:none;}
body::before{width:500px;height:500px;background:radial-gradient(circle,#7c3aed,#4f46e5);top:-150px;left:-100px;}
body::after{width:400px;height:400px;background:radial-gradient(circle,#06b6d4,#3b82f6);bottom:-100px;right:-100px;animation-delay:-4s;}
@keyframes float{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(28px) scale(1.07)}}
.card{position:relative;z-index:10;width:100%;max-width:440px;margin:1rem;background:rgba(255,255,255,.06);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:2.25rem 2rem;box-shadow:0 25px 60px rgba(0,0,0,.4);animation:slideUp .6s cubic-bezier(.16,1,.3,1) both;}
@keyframes slideUp{from{opacity:0;transform:translateY(38px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.logo{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:15px;background:linear-gradient(135deg,#7c3aed,#4f46e5);margin:0 auto 1.25rem;box-shadow:0 8px 22px rgba(124,58,237,.4);}
.logo svg{width:26px;height:26px;fill:#fff;}
h1{text-align:center;color:#fff;font-size:1.5rem;font-weight:700;letter-spacing:-.02em;margin-bottom:.3rem;}
.sub{text-align:center;color:rgba(255,255,255,.45);font-size:.85rem;margin-bottom:1.75rem;}
.fg{margin-bottom:.95rem;}
label{display:block;color:rgba(255,255,255,.7);font-size:.8rem;font-weight:500;margin-bottom:.4rem;letter-spacing:.02em;}
.iw{position:relative;}
.iw svg{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);width:16px;height:16px;fill:none;stroke:rgba(255,255,255,.3);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;pointer-events:none;transition:stroke .2s;}
input{width:100%;padding:.72rem .9rem .72rem 2.6rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:12px;color:#fff;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s,background .2s,box-shadow .2s;}
input::placeholder{color:rgba(255,255,255,.22);}
input:focus{border-color:#7c3aed;background:rgba(124,58,237,.12);box-shadow:0 0 0 3px rgba(124,58,237,.2);}
.iw:focus-within svg{stroke:#a78bfa;}
.btn{width:100%;padding:.82rem;margin-top:.4rem;background:linear-gradient(135deg,#7c3aed,#4f46e5);border:none;border-radius:12px;color:#fff;font-size:.95rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;box-shadow:0 4px 18px rgba(124,58,237,.45);transition:transform .18s,box-shadow .18s,filter .18s;}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 26px rgba(124,58,237,.55);filter:brightness(1.08);}
.btn:active{transform:translateY(0);}
.alert{display:flex;align-items:center;gap:.5rem;padding:.7rem .9rem;border-radius:11px;font-size:.85rem;margin-bottom:1rem;}
.alert-e{background:rgba(239,68,68,.13);border:1px solid rgba(239,68,68,.4);color:#fca5a5;animation:shake .4s ease;}
.alert-s{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#6ee7b7;}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}40%{transform:translateX(5px)}60%{transform:translateX(-3px)}80%{transform:translateX(3px)}}
.footer-link{text-align:center;margin-top:1.4rem;color:rgba(255,255,255,.4);font-size:.85rem;}
.footer-link a{color:#a78bfa;font-weight:500;text-decoration:none;transition:color .2s;}
.footer-link a:hover{color:#c4b5fd;}
.divider{display:flex;align-items:center;gap:.7rem;margin:1.4rem 0 0;color:rgba(255,255,255,.2);font-size:.73rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.1);}
</style>
</head>
<body>
<div class="card">
  <div class="logo"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
  <h1>Create account</h1>
  <p class="sub">Join Portfolio Generator today</p>
  <?php if($error): ?><div class="alert alert-e"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= $error ?></div><?php endif; ?>
  <?php if($success): ?><div class="alert alert-s"><?= $success ?></div><?php endif; ?>
  <?php if(!$success): ?>
  <form method="POST" id="regform">
    <div class="fg"><label>Username</label><div class="iw"><input type="text" name="username" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username']??'') ?>"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div></div>
    <div class="fg"><label>Email</label><div class="iw"><input type="email" name="email" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div></div>
    <div class="fg"><label>Password</label><div class="iw"><input type="password" name="password" placeholder="Min. 6 characters" required><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div></div>
    <button type="submit" class="btn">Create Account</button>
  </form>
  <?php endif; ?>
  <div class="divider">or</div>
  <p class="footer-link">Already have an account? <a href="login.php">Sign in</a></p>
</div>
</body>
</html>