<?php
require_once '../config/db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, account_status) VALUES (?, ?, ?, 'pending')");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = "Registration successful! Please wait for an admin to approve your account.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Portfolio Generator</title>
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
            background-image: url('https://static.vecteezy.com/system/resources/previews/016/143/128/non_2x/register-now-icon-in-comic-style-registration-cartoon-illustration-on-isolated-background-member-notification-splash-effect-sign-business-concept-vector.jpg');
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
            <!-- Form on left for register -->
            <div class="auth-form-wrapper">
                <h2 style="margin-bottom: 2rem; color: #fff;">Create Account</h2>
                <?php if ($error): ?>
                    <div class="msg-error" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="msg-success" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
                    <p style="text-align: center;"><a href="login.php" class="btn" style="margin-top: 1rem; display: inline-block;">Go to Login</a></p>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Username</label>
                            <input type="text" name="username" required placeholder="Choose a username">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="Enter your email">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Password</label>
                            <div style="position: relative;">
                                <input type="password" name="password" id="password" required placeholder="Create a password" style="padding-right: 40px;">
                                <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted);" onclick="togglePassword('password', this)"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Register</button>
                    </form>
                    <p style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted); text-align: center;">
                        Already have an account? <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Login here</a>
                    </p>
                <?php endif; ?>
            </div>
            <!-- Image on right for register -->
            <div class="auth-image"></div>
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