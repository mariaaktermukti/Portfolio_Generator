<?php
require_once '../config/config/db.php';
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :uname AND is_deleted = 0");
    $stmt->execute([':uname' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ../dashboard/index.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portfolio Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0c29;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            overflow: hidden;
            position: relative;
        }

        /* Animated blobs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #7c3aed, #4f46e5);
            top: -150px; left: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #06b6d4, #3b82f6);
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(30px) scale(1.08); }
        }

        /* Card */
        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            margin: 1rem;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Logo / Icon */
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px; height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(124,58,237,0.4);
        }
        .logo svg { width: 28px; height: 28px; fill: #fff; }

        h1 {
            text-align: center;
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.45);
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        /* Error */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)}
            20%{transform:translateX(-6px)}
            40%{transform:translateX(6px)}
            60%{transform:translateX(-4px)}
            80%{transform:translateX(4px)}
        }

        /* Form */
        .form-group { margin-bottom: 1.1rem; }

        label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 0.45rem;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }
        .input-wrap svg {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            width: 17px; height: 17px;
            fill: none; stroke: rgba(255,255,255,0.35);
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
            pointer-events: none;
            transition: stroke 0.2s;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.78rem 1rem 0.78rem 2.75rem;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
        }
        input::placeholder { color: rgba(255,255,255,0.25); }
        input:focus {
            border-color: #7c3aed;
            background: rgba(124,58,237,0.12);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.2);
        }
        input:focus + svg, .input-wrap:focus-within svg {
            stroke: #a78bfa;
        }

        /* Password toggle */
        .toggle-pw {
            position: absolute;
            right: 0.9rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            padding: 0.2rem;
            display: flex; align-items: center;
        }
        .toggle-pw svg {
            position: static; transform: none;
            width: 17px; height: 17px;
            stroke: rgba(255,255,255,0.35);
            transition: stroke 0.2s;
        }
        .toggle-pw:hover svg { stroke: rgba(255,255,255,0.7); }

        /* Submit */
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            margin-top: 0.5rem;
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 20px rgba(124,58,237,0.45);
            transition: transform 0.18s, box-shadow 0.18s, filter 0.18s;
            position: relative;
            overflow: hidden;
        }
        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(124,58,237,0.55); filter: brightness(1.08); }
        .btn-login:hover::after { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        /* Footer link */
        .footer-link {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.4);
            font-size: 0.875rem;
        }
        .footer-link a {
            color: #a78bfa;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link a:hover { color: #c4b5fd; text-decoration: underline; }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.5rem 0 0;
            color: rgba(255,255,255,0.2);
            font-size: 0.75rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="card">

        <!-- Logo -->
        <div class="logo">
            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>

        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to your Portfolio Generator</p>

        <?php if (isset($error)): ?>
        <div class="alert-error" id="login-error">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="login-form" novalidate>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <input type="text" id="username" name="username" placeholder="Enter your username" required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <button type="button" class="toggle-pw" id="toggle-pw" aria-label="Show password">
                        <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="submit-btn">Sign In</button>
        </form>

        <div class="divider">or</div>

        <p class="footer-link">Don't have an account? <a href="register.php">Create one</a></p>
    </div>

    <script>
        // Password toggle
        const toggleBtn = document.getElementById('toggle-pw');
        const pwInput   = document.getElementById('password');
        const eyeIcon   = document.getElementById('eye-icon');
        toggleBtn.addEventListener('click', () => {
            const show = pwInput.type === 'password';
            pwInput.type = show ? 'text' : 'password';
            eyeIcon.innerHTML = show
                ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
                : '<path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
        });

        // Button loading state
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            btn.textContent = 'Signing in…';
            btn.style.opacity = '0.75';
            btn.disabled = true;
        });
    </script>
</body>
</html>