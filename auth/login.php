<?php
require_once '../config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_deleted = 0");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['account_status'] === 'pending') {
                $error = "Your account is pending admin approval at the moment. Please check back later.";
            } elseif ($user['account_status'] === 'rejected') {
                $error = "Sorry, your account registration has been rejected.";
            } elseif ($user['account_status'] === 'paused') {
                $error = "Your account is currently paused/inactive. Please contact the administrator.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                if ($user['is_admin']) {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../dashboard/index.php');
                }
                exit;
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portfolio Generator</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            background: var(--bg-primary);
        }
        .auth-container {
            display: flex;
            max-width: 1280px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        .auth-image {
            flex: 1;
            background-image: url('https://assets-v2.lottiefiles.com/a/59ae3046-117b-11ee-88a7-ef3838e9662f/r8HuxylbzH.gif');
            background-size: cover;
            background-position: center;
            display: none;
        }
        @media(min-width: 768px) {
            .auth-image { display: block; }
        }
        .auth-form-wrapper {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-image"></div>
            <div class="auth-form-wrapper">
                <h2 style="margin-bottom: 2rem; color: #fff;">Welcome Back</h2>
                <?php if ($error): ?>
                    <div class="msg-error" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required placeholder="Enter your username">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="password" required placeholder="Enter your password" style="padding-right: 40px;">
                            <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted);" onclick="togglePassword('password', this)"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Login</button>
                </form>
                <p style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted); text-align: center;">
                    Don't have an account? <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Register here</a>
                </p>
            </div>
        </div>
    </div>
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>