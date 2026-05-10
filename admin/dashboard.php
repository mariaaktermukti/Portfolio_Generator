<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <div class="glass-panel" style="text-align: center; margin-bottom: 2rem;">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <p style="color: var(--text-muted); font-size: 1.2rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>

        <div class="card-grid">
            <a href="approve_users.php" class="card" style="text-align: center; text-decoration: none;">
                <i class="fas fa-users-cog" style="font-size: 3rem; color: var(--accent); margin-bottom: 1rem;"></i>
                <h3>Approve Users</h3>
                <p style="color: var(--text-muted);">Manage pending registrations.</p>
            </a>
            <a href="../analytics/analytics.php" class="card" style="text-align: center; text-decoration: none;">
                <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                <h3>Platform Analytics</h3>
                <p style="color: var(--text-muted);">View global platform stats and top users.</p>
            </a>
            <a href="../auth/logout.php" class="card" style="text-align: center; text-decoration: none;">
                <i class="fas fa-sign-out-alt" style="font-size: 3rem; color: var(--danger); margin-bottom: 1rem;"></i>
                <h3>Logout</h3>
                <p style="color: var(--text-muted);">End your admin session safely.</p>
            </a>
        </div>
    </div>
</body>
</html>
